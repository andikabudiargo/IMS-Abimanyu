<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use DB;

/**
 * Move/merge one COA account into another: every column across the schema
 * that stores a COA code (account/coa/akun references, discovered live via
 * information_schema instead of a hardcoded list that would rot as modules
 * get added) gets repointed from the source code to the target code, so the
 * target absorbs the source's documents and balances.
 *
 * Deliberately does NOT check period locks (application_lock / Lock
 * Transaction) -- a COA move must go through regardless of whether the
 * affected documents' period is locked. Every table.column touched is
 * written to coa_move_log so the move is traceable.
 */
class CoaMoveController extends Controller
{
    /**
     * Columns that match the name pattern below but are not a COA code
     * (bank account numbers/names, not chart-of-accounts codes).
     */
    private const EXCLUDE_COLUMNS = [
        'account_number', 'account_name', 'bank_account_name', 'account_nmber',
    ];

    /** Snapshot/report/backup tables: never touch, they are point-in-time records. */
    private const EXCLUDE_TABLE_PATTERNS = [
        '%_saved', '%_copy_%', '%_bak', '%_backup', 'master_neraca%', 'coa_move_log',
    ];

    /**
     * List every table.column that currently holds the source account code,
     * for the include/except checklist.
     */
    public function scan(Request $request)
    {
        $request->validate(['source' => 'required|string', 'target' => 'required|string']);
        $source = strtoupper($request->source);
        $target = strtoupper($request->target);

        if ($source === $target) {
            return response()->json(['message' => 'Source and target cannot be the same account.'], 422);
        }

        foreach (['source' => $source, 'target' => $target] as $label => $code) {
            if (!DB::table('accounts')->where('account', $code)->exists()) {
                return response()->json(['message' => "Account \"$code\" ($label) was not found in Chart of Accounts."], 422);
            }
        }

        $candidates = $this->candidateColumns();
        $rows = [];
        foreach ($candidates as $c) {
            $count = DB::table($c->table_name)->where($c->column_name, $source)->count();
            $rows[] = [
                'table' => $c->table_name,
                'column' => $c->column_name,
                'count' => $count,
                'checked' => $count > 0,
            ];
        }

        return response()->json(['source' => $source, 'target' => $target, 'rows' => $rows]);
    }

    public function execute(Request $request)
    {
        $request->validate([
            'source' => 'required|string',
            'target' => 'required|string',
            'selected' => 'required|array|min:1',
        ]);
        $source = strtoupper($request->source);
        $target = strtoupper($request->target);

        $allowed = collect($this->candidateColumns())
            ->map(fn ($c) => $c->table_name . '.' . $c->column_name)
            ->flip();

        $batchId = (string) Str::uuid();
        $username = Auth::user()->username;
        $summary = [];

        DB::beginTransaction();
        try {
            foreach ($request->selected as $pair) {
                if (!isset($allowed[$pair])) {
                    continue; // not a real/allowed column, ignore client tampering
                }
                [$table, $column] = explode('.', $pair, 2);

                $pkColumn = $this->primaryKeyOf($table);
                $affectedPks = $pkColumn
                    ? DB::table($table)->where($column, $source)->pluck($pkColumn)
                    : null;

                $rowCount = DB::table($table)->where($column, $source)->update([$column => $target]);

                DB::table('coa_move_log')->insert([
                    'batch_id' => $batchId,
                    'source_account' => $source,
                    'target_account' => $target,
                    'table_name' => $table,
                    'column_name' => $column,
                    'row_count' => $rowCount,
                    'affected_pks' => $affectedPks !== null ? json_encode($affectedPks) : null,
                    'moved_by' => $username,
                    'moved_at' => now(),
                ]);

                $summary[] = "$table.$column: $rowCount row(s)";
            }

            if ($request->boolean('deactivateSource')) {
                DB::table('accounts')->where('account', $source)->update([
                    'status' => '0',
                    'updated_by' => $username,
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            \LogActivity::addToLog('COA Move', "username: $username moved $source -> $target (batch $batchId): " . implode('; ', $summary));

            return response()->json(['message' => 'COA move completed.', 'batch' => $batchId, 'summary' => $summary]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'COA move failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Every varchar/text column across the schema that plausibly stores a
     * COA code: name contains account/coa/akun, minus known false positives
     * and snapshot tables -- plus accounts.parent_id, which links a child
     * account to its header account by code but doesn't match that pattern.
     */
    private function candidateColumns()
    {
        $exclude = implode(',', array_map(fn ($c) => "'$c'", self::EXCLUDE_COLUMNS));
        $tableExclusions = implode(' AND ', array_map(
            fn ($p) => "table_name NOT ILIKE '$p'",
            self::EXCLUDE_TABLE_PATTERNS
        ));

        $rows = DB::select("
            SELECT table_name, column_name
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND data_type IN ('character varying', 'text', 'character')
              AND column_name NOT IN ($exclude)
              AND column_name ~* '(account|coa|akun)'
              AND $tableExclusions
            ORDER BY table_name, column_name
        ");

        $rows[] = (object) ['table_name' => 'accounts', 'column_name' => 'parent_id'];

        return $rows;
    }

    private function primaryKeyOf(string $table): ?string
    {
        $pk = DB::selectOne("
            SELECT a.attname AS column_name
            FROM pg_index i
            JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
            WHERE i.indrelid = ?::regclass AND i.indisprimary AND array_length(i.indkey, 1) = 1
        ", [$table]);

        return $pk->column_name ?? null;
    }
}
