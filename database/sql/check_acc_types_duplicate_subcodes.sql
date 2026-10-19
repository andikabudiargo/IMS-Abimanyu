-- Curiga: acc_types berisi baris yang sebenarnya Sub Account (acc_sub), bukan Account Type asli.
-- Account Type asli kodenya murni angka (1100, 1200, 2000, ...). Baris acc_types yang kodenya
-- mengandung '.' (misal 1100.10) kemungkinan salah input dan sudah PUNYA padanan yang benar
-- di acc_sub (type_code=1100, sub_code=1100.10).
--
-- Kolom used_by_accounts menunjukkan apakah ada akun (accounts.type_code) yang masih
-- menunjuk ke kode "type" yang salah ini -- kalau ada, jangan dihapus/dinonaktifkan dulu
-- sebelum akun tsb dipindah ke type_code yang benar.
SELECT
    t.id,
    t.code            AS bad_type_code,
    t.name            AS bad_type_name,
    t.status,
    split_part(t.code, '.', 1) AS kemungkinan_type_code_asli,
    EXISTS (
        SELECT 1 FROM acc_sub s
        WHERE s.sub_code = t.code
    ) AS sudah_ada_di_acc_sub,
    (SELECT count(*) FROM accounts a WHERE a.type_code = t.code) AS used_by_accounts
FROM acc_types t
WHERE position('.' IN t.code) > 0
ORDER BY t.code;
