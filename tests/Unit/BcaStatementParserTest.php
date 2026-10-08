<?php

namespace Tests\Unit;

use App\Services\BcaStatementParser;
use PHPUnit\Framework\TestCase;

class BcaStatementParserTest extends TestCase
{
    // Teks ini meniru persis contoh yang dikirim user: header periode, 3 baris transaksi
    // (termasuk SALDO AWAL dan satu transaksi dengan description 2 baris lanjutan),
    // ditutup ringkasan SALDO AWAL/MUTASI CR/MUTASI DB/SALDO AKHIR di akhir PDF.
    private function sampleText(): string
    {
        return <<<TXT
HALAMAN : 1 / 39
PERIODE : JANUARI 2026
MATA UANG : IDR
CATATAN:
Apabila nasabah tidak melakukan sanggahan...
BCA berhak setiap saat melakukan koreksi...
TANGGAL KETERANGAN CBG MUTASI SALDO
01/01 SALDO AWAL 66,864,584.15
05/01 FLAZZ BCA 514,000.00 DB 66,350,584.15
TOPUP ABIMANYU SEK
0145008400320692
05/01 FLAZZ BCA 54,000.00 DB
0145008401589401
SALDO AWAL : 66,864,584.15
MUTASI CR : 209,821,746.89 25
MUTASI DB : 242,803,816.98 349
SALDO AKHIR : 33,882,514.06
TXT;
    }

    public function testParsesTransactionRowsAndSkipsSaldoAwal()
    {
        $result = (new BcaStatementParser())->parseText($this->sampleText());

        $this->assertSame(2026, $result['year']);
        $this->assertSame(1, $result['month']);
        $this->assertCount(2, $result['rows']);
    }

    public function testFirstRowHasDateAmountTypeSaldoAndMultilineDescription()
    {
        $rows = (new BcaStatementParser())->parseText($this->sampleText())['rows'];

        $this->assertSame('2026-01-05', $rows[0]['stmt_date']);
        $this->assertSame(514000.0, $rows[0]['amount']);
        $this->assertSame('DB', $rows[0]['mutation_type']);
        $this->assertSame(66350584.15, $rows[0]['saldo']);
        $this->assertStringContainsString('FLAZZ BCA', $rows[0]['description']);
        $this->assertStringContainsString('TOPUP ABIMANYU SEK', $rows[0]['description']);
        $this->assertStringContainsString('0145008400320692', $rows[0]['description']);
    }

    public function testSecondRowWithoutTrailingSaldoStillParses()
    {
        $rows = (new BcaStatementParser())->parseText($this->sampleText())['rows'];

        $this->assertSame(54000.0, $rows[1]['amount']);
        $this->assertSame('DB', $rows[1]['mutation_type']);
        $this->assertNull($rows[1]['saldo']);
        $this->assertStringContainsString('0145008401589401', $rows[1]['description']);
    }

    public function testExtractsClosingSummaryChecksum()
    {
        $summary = (new BcaStatementParser())->parseText($this->sampleText())['summary'];

        $this->assertSame(66864584.15, $summary['saldo_awal']);
        $this->assertSame(209821746.89, $summary['mutasi_cr']);
        $this->assertSame(25, $summary['count_cr']);
        $this->assertSame(242803816.98, $summary['mutasi_db']);
        $this->assertSame(349, $summary['count_db']);
        $this->assertSame(33882514.06, $summary['saldo_akhir']);
    }

    // Skenario dari screenshot ke-3: description "DB" di tengah kalimat (bukan di akhir
    // sebagai mutation type), description 5 baris, dan footer "Bersambung ke halaman
    // berikut" di antara 2 transaksi yang TIDAK boleh ke-nempel ke description manapun
    // ataupun bikin baris transaksi setelahnya ke-skip.
    private function pageBreakSampleText(): string
    {
        return <<<TXT
TANGGAL KETERANGAN CBG MUTASI SALDO
30/01 TRSF E-BANKING DB
3001/FTSCY/WS95051 25,000.00 DB 39,226,628.15
25000.00
PERJALANAN DINAS
KK-ASN-26-I-0332
MAR'ATUR ROIFAH

Bersambung ke halaman berikut
31/01 FLAZZ BCA 100,000.00 DB 39,126,628.15
TXT;
    }

    public function testDbInsideDescriptionDoesNotConfuseAmountParsing()
    {
        $rows = (new BcaStatementParser())->parseText($this->pageBreakSampleText())['rows'];

        $this->assertSame(39226628.15, $rows[0]['saldo']);
        $this->assertSame(25000.0, $rows[0]['amount']);
        $this->assertSame('DB', $rows[0]['mutation_type']);
        $this->assertStringContainsString('TRSF E-BANKING DB 3001/FTSCY/WS95051', $rows[0]['description']);
        $this->assertStringContainsString("MAR'ATUR ROIFAH", $rows[0]['description']);
    }

    public function testPageBreakFooterIsDroppedAndNextTransactionStillParsed()
    {
        $rows = (new BcaStatementParser())->parseText($this->pageBreakSampleText())['rows'];

        $this->assertCount(2, $rows);
        $this->assertStringNotContainsString('Bersambung', $rows[0]['description']);
        $this->assertSame(100000.0, $rows[1]['amount']);
        $this->assertSame('2026-01-31', $rows[1]['stmt_date']);
    }
}
