<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpBrtLabels\Helpers;

class Bordero
{
    public static function getFakeData(int $rowsCount = 14): array
    {
        $company = '[1690519] IMPRENDO S.R.L.S. - Via Mafalda di Savoia 28-30 - 87013 FAGNANO CASTELLO (CS)';
        $now = date('d/m/Y H:i:s');

        $cities = [
            'BRUGHERIO - MB',
            'PITIGLIANO - GR',
            'SAN MAURO PASCOLI - FC',
            'MONTESILICE - PD',
            'SIGNA - FI',
            'CASARANO - LE',
            'GRASSO - GR',
            'PATERNO - PA',
            'MONTEFIASCONE - VT',
        ];
        $names = [
            'CLARISSA CIANCI',
            "TIPO DI SERVIZIO: C\nMICHELE TEDDE",
            "SOCIETA AGRICOLA CHICOCROSSO SRL\nTIPO DI SERVIZIO: C",
            "MONICA NAVARRO\nTIPO DI SERVIZIO: C",
            "ROSA ELVIRA CINI\nTIPO DI SERVIZIO: C",
            "LUCA POLI\nTIPO DI SERVIZIO: C",
            "STEFANIA SANNA\nTIPO DI SERVIZIO: C",
            "FOODA&FOOD SRL\nTIPO DI SERVIZIO: C",
        ];
        $addresses = [
            'PIAZZA IV NOVEMBRE, 12',
            'STRADA OMBRONE, 82',
            'VIA ROMA, 8',
            'VIA LIGURIA, 8',
            'VIA DANTE, 10',
            'VIA VITTORIO EMANUELE, 232',
            'VIA MAURO, 4',
            'VIALE BONANIO, 5',
        ];

        $rows = [];
        $totalCod = 0.0;
        $totalWeight = 0.0;
        $totalVolume = 0.0;
        $codOrders = 0;

        for ($i = 0; $i < $rowsCount; $i++) {
            $nsr = 1000507000 + $i * random_int(1, 5);
            $name = $names[array_rand($names)];
            $addr = $addresses[array_rand($addresses)];
            $city = $cities[array_rand($cities)];
            $cassa = random_int(0, 1) ? (random_int(0, 8000) / 100) : 0.0;
            $colli = 1;
            $peso = (random_int(10, 350) / 10);
            $vol = (random_int(0, 80) / 1000);
            $segna = str_pad((string) random_int(8500000, 8599999), 7, '0', STR_PAD_LEFT);

            if ($cassa > 0) {
                $totalCod += $cassa;
                $codOrders++;
            }
            $totalWeight += $peso;
            $totalVolume += $vol;

            $rows[] = [
                'destinatario' => $name,
                'indirizzo' => $addr . "\n" . $city,
                'rif_num' => (string) $nsr,
                'cod_bolla' => '',
                'importo_cassa' => $cassa,
                'colli' => $colli,
                'peso' => $peso,
                'volume' => $vol,
                'segnacolli' => $segna,
            ];
        }

        return [
            'header' => [
                'company' => $company,
                'generatedAt' => $now,
            ],
            'rows' => $rows,
            'summary' => [
                'totale_spedizioni' => count($rows),
                'totale_colli' => array_sum(array_map(static fn($r) => (int) $r['colli'], $rows)),
                'totale_contrassegni_ordini' => $codOrders,
                'importo_contrassegni' => $totalCod,
                'totale_peso' => $totalWeight,
                'totale_volume' => $totalVolume,
            ],
        ];
    }

    public static function render(array $data): string
    {
        if (!class_exists('TCPDF')) {
            throw new \RuntimeException('TCPDF non disponibile');
        }

        $header = $data['header'] ?? [];
        $rows = $data['rows'] ?? [];
        $summary = $data['summary'] ?? [];

        $pdf = new class('L', 'mm', 'A4', true, 'UTF-8', false) extends \TCPDF {
            public string $footerDate = '';

            public function Footer()
            {
                $this->SetY(-15);
                $this->SetLineWidth(0.2);
                $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());

                $this->SetFont('helvetica', '', 7);
                $this->SetY(-11);
                $this->Cell(0, 4, $this->footerDate, 0, 0, 'C');
                $this->SetY(-11);
                $this->Cell(0, 4, $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
            }
        };

        $pdf->SetCreator('TCPDF');
        $pdf->SetAuthor('Massimiliano Palermo');
        $pdf->SetTitle('Bordero');
        $pdf->SetSubject('Bordero per Bartolini');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->footerDate = (string) ($header['generatedAt'] ?? '');

        $pdf->AddPage('L', 'A4');
        self::renderHeader($pdf, (string) ($header['company'] ?? ''));
        self::renderRowsTable($pdf, $rows);

        $pdf->AddPage('L', 'A4');
        self::renderHeader($pdf, (string) ($header['company'] ?? ''));
        self::renderSummary($pdf, $summary);
        self::renderSignature($pdf);

        return $pdf->Output('', 'S');
    }

    private static function renderHeader(\TCPDF $pdf, string $company): void
    {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetY(8);
        $pdf->Cell(0, 5, $company, 0, 1, 'C');
        $pdf->SetLineWidth(0.2);
        $pdf->Line(10, 18, $pdf->getPageWidth() - 10, 18);
        $pdf->Ln(6);
    }

    private static function renderRowsTable(\TCPDF $pdf, array $rows): void
    {
        $w = [62, 70, 22, 18, 20, 12, 18, 20, 22];

        $startX = 10;
        $pdf->SetX($startX);
        $pdf->SetFont('helvetica', '', 7);

        $pdf->MultiCell($w[0], 10, 'Destinatario', 0, 'L', false, 0);
        $pdf->MultiCell($w[1], 10, "Indirizzo\nCap Città Prov", 0, 'L', false, 0);
        $pdf->MultiCell($w[2], 10, "Rif. numerico\nRiferimento", 0, 'L', false, 0);
        $pdf->MultiCell($w[3], 10, "Cod\nBolla", 0, 'L', false, 0);
        $pdf->MultiCell($w[4], 10, "Importo\nC/ass", 0, 'L', false, 0);
        $pdf->MultiCell($w[5], 10, 'Colli', 0, 'C', false, 0);
        $pdf->MultiCell($w[6], 10, 'Peso', 0, 'C', false, 0);
        $pdf->MultiCell($w[7], 10, 'Volume', 0, 'C', false, 0);
        $pdf->MultiCell($w[8], 10, "Segnacolli\nDa - Al", 0, 'L', false, 1);

        $pdf->SetLineWidth(0.15);
        $pdf->Line(10, $pdf->GetY(), $pdf->getPageWidth() - 10, $pdf->GetY());
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 7.5);
        $lineH = 3.5;

        foreach ($rows as $row) {
            $dest = (string) ($row['destinatario'] ?? '');
            $addr = (string) ($row['indirizzo'] ?? '');
            $rif = (string) ($row['rif_num'] ?? '');
            $bolla = (string) ($row['cod_bolla'] ?? '');
            $cassa = (float) ($row['importo_cassa'] ?? 0);
            $colli = (int) ($row['colli'] ?? 0);
            $peso = (float) ($row['peso'] ?? 0);
            $vol = (float) ($row['volume'] ?? 0);
            $segna = (string) ($row['segnacolli'] ?? '');

            $lines0 = max(1, $pdf->getNumLines($dest, $w[0]));
            $lines1 = max(1, $pdf->getNumLines($addr, $w[1]));
            $h = max($lines0, $lines1) * $lineH;

            if ($pdf->GetY() + $h > ($pdf->getPageHeight() - 20)) {
                $pdf->AddPage('L', 'A4');
                self::renderHeader($pdf, '');
            }

            $addrArray = explode("\n", $addr);
            $segnaArray = explode("\n", $segna);

            $pdf->SetX($startX);
            $pdf->setFont('', '', 7);

            $pdf->MultiCell($w[0], $h, $dest, 0, 'L', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[1], $h, $addrArray[0], 0, 'L', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[2], $h, $rif, 0, 'L', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[3], $h, '', 0, 'L', false, 0, null, null, true, 0, false, true, $h, 'T');
            if ($cassa > 0) {
                $pdf->setFont('', 'B', 7);
            }
            $pdf->MultiCell($w[4], $h, $cassa > 0 ? number_format($cassa, 2, ',', '.') . ' €' : '--', 0, 'R', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->setFont('', '', 7);
            $pdf->MultiCell($w[5], $h, (string) $colli, 0, 'C', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[6], $h, number_format($peso, 1, ',', '.') . ' Kg', 0, 'R', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[7], $h, number_format($vol, 3, ',', '.') . ' m3', 0, 'R', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[8], $h, $segnaArray[0], 0, 'L', false, 1, null, null, true, 0, false, true, $h, 'T');

            $pdf->SetX($startX);
            $pdf->SetY($pdf->getY() - 4);
            $pdf->setFont('', 'B', 7);

            $pdf->MultiCell($w[0], $h, '', 0, 'L', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[1], $h, $addrArray[1], 0, 'L', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[2], $h, $bolla, 0, 'L', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[3], $h, '', 0, 'L', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[4], $h, '', 0, 'R', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[5], $h, '', 0, 'C', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[6], $h, '', 0, 'R', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[7], $h, '', 0, 'R', false, 0, null, null, true, 0, false, true, $h, 'T');
            $pdf->MultiCell($w[8], $h, $segnaArray[1], 0, 'L', false, 1, null, null, true, 0, false, true, $h, 'T');

            $pdf->SetY($pdf->getY() - 2);
        }
    }

    private static function renderSummary(\TCPDF $pdf, array $summary): void
    {
        $pdf->SetFont('helvetica', 'B', 10);
        $x = 12;
        $y = 28;
        $w = 110;
        $pdf->Rect($x, $y, $w, 55);

        $pdf->SetXY($x + 2, $y + 2);
        $pdf->Cell($w - 4, 6, 'RIEPILOGO', 0, 1, 'L');
        $pdf->Line($x, $y + 10, $x + $w, $y + 10);

        $pdf->SetFont('helvetica', '', 9);
        $labels = [
            ['TOTALE SPEDIZIONI:', (int) ($summary['totale_spedizioni'] ?? 0), 'SPED'],
            ['TOTALE COLLI:', (int) ($summary['totale_colli'] ?? 0), 'COLLI'],
            ['TOTALE CONTRASSEGNI:', (int) ($summary['totale_contrassegni_ordini'] ?? 0), 'ORDINI'],
            ['IMPORTO CONTRASSEGNI:', number_format((float) ($summary['importo_contrassegni'] ?? 0), 2, ',', '.'), 'EUR'],
            ['TOTALE PESO:', number_format((float) ($summary['totale_peso'] ?? 0), 2, ',', '.'), 'Kg'],
            ['TOTALE VOLUME:', number_format((float) ($summary['totale_volume'] ?? 0), 3, ',', '.'), 'M³'],
        ];

        $lineY = $y + 12;
        foreach ($labels as $i => $row) {
            $pdf->SetXY($x + 2, $lineY + ($i * 5));
            $pdf->Cell(70, 5, $row[0], 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(25, 5, (string) $row[1], 0, 0, 'R');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(10, 5, (string) $row[2], 0, 1, 'L');
        }
    }

    private static function renderSignature(\TCPDF $pdf): void
    {
        $pdf->SetFont('helvetica', '', 10);
        $y = 120;
        $pdf->SetXY($pdf->getPageWidth() - 70, $y);
        $pdf->Cell(60, 6, 'FIRMA', 0, 1, 'L');
        $pdf->SetXY($pdf->getPageWidth() - 95, $y + 18);
        $pdf->Cell(80, 6, str_repeat('-', 50), 0, 1, 'L');
    }
}
