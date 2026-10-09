<?php

namespace Tests\Unit;

use App\Services\BcaStatementParser;
use PHPUnit\Framework\TestCase;

class BcaStatementParserTest extends TestCase
{
    // File asli hasil export KlikBCA Bisnis yang dikirim user (142 baris transaksi,
    // saldo akun ini overdraft/negatif, checksum 118 baris DB + 24 baris CR).
    private function fixturePath(): string
    {
        return __DIR__ . '/../Fixtures/bca_statement_sample.csv';
    }

    public function testParsesAllTransactionRowsAndYear()
    {
        $result = (new BcaStatementParser())->parseFile($this->fixturePath());

        $this->assertSame(2026, $result['year']);
        $this->assertCount(142, $result['rows']);
    }

    public function testFirstRowCreditTransaction()
    {
        $rows = (new BcaStatementParser())->parseFile($this->fixturePath())['rows'];

        $this->assertSame('2026-01-02', $rows[0]['stmt_date']);
        $this->assertSame('BI-FAST CR TRANSFER   DR 008 USRA TAMPI INDONES', $rows[0]['description']);
        $this->assertSame(119765310.0, $rows[0]['amount']);
        $this->assertSame('CR', $rows[0]['mutation_type']);
        $this->assertSame(-5996961559.59, $rows[0]['saldo']);
    }

    public function testDescriptionContainingDbTextDoesNotConfuseColumnParsing()
    {
        // Baris ke-7 di file: Keterangan-nya literally mengandung kata "DB" di tengah
        // kalimat ("TRSF E-BANKING DB ..."), tapi karena CSV kolomnya sudah terpisah,
        // tidak ada ambiguitas sama sekali (beda dengan kasus waktu masih parsing PDF).
        $rows = (new BcaStatementParser())->parseFile($this->fixturePath())['rows'];
        $row = $rows[6];

        $this->assertStringContainsString('TRSF E-BANKING DB 0701/FTFVA/WS95051', $row['description']);
        $this->assertSame(18720928.0, $row['amount']);
        $this->assertSame('DB', $row['mutation_type']);
    }

    public function testNegativeSaldoParsedCorrectly()
    {
        // Akun ini overdraft (saldo selalu negatif) -- pastikan tanda minus tidak hilang.
        $rows = (new BcaStatementParser())->parseFile($this->fixturePath())['rows'];

        foreach ($rows as $row) {
            $this->assertLessThan(0, $row['saldo'], "Saldo baris tanggal {$row['stmt_date']} harusnya negatif");
        }
    }

    public function testClosingSummaryChecksumMatchesFileFooter()
    {
        $summary = (new BcaStatementParser())->parseFile($this->fixturePath())['summary'];

        $this->assertSame(-6116726869.59, $summary['saldo_awal']);
        $this->assertSame(5415652878.56, $summary['mutasi_db']);
        $this->assertSame(118, $summary['count_db']);
        $this->assertSame(5243923666.50, $summary['mutasi_cr']);
        $this->assertSame(24, $summary['count_cr']);
        $this->assertSame(-6288456081.65, $summary['saldo_akhir']);
    }

    public function testParsedRowCountsAndSumsMatchChecksum()
    {
        // Validasi silang: jumlah & total baris DB/CR hasil parse harus persis sama
        // dengan checksum yang dideklarasikan file itu sendiri di baris penutup.
        $result = (new BcaStatementParser())->parseFile($this->fixturePath());
        $rows = $result['rows'];
        $summary = $result['summary'];

        $dbRows = array_filter($rows, fn ($r) => $r['mutation_type'] === 'DB');
        $crRows = array_filter($rows, fn ($r) => $r['mutation_type'] === 'CR');

        $this->assertCount($summary['count_db'], $dbRows);
        $this->assertCount($summary['count_cr'], $crRows);
        $this->assertEqualsWithDelta($summary['mutasi_db'], array_sum(array_column($dbRows, 'amount')), 0.01);
        $this->assertEqualsWithDelta($summary['mutasi_cr'], array_sum(array_column($crRows, 'amount')), 0.01);
    }
}
