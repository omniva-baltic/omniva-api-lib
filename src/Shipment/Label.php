<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;
use Mijora\Omniva\Request;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

class Label
{

    /**
     * @var Request
     */
    private $request;

    /*
     * @param string $username
     * @param string $password
     * @param string $api_url
     */

    public function setAuth($username, $password, $api_url = 'https://edixml.post.ee')
    {
        $this->request = new Request($username, $password, $api_url);
    }

    /*
     * @param array $barcodes
     * @return mixed
     */

    public function getLabels($barcodes)
    {
        if (!is_array($barcodes)) {
            $barcodes = [$barcodes];
        }
        if (empty($this->request)) {
            throw new OmnivaException("Please set username and password");
        }
        $result = $this->request->get_labels($barcodes);
        return $result;
    }

    /*
     * $mode - I inline, D download, F save to file, S - return string
     * @param array $barcodes
     * @param bool $combine
     * @param string $mode
     * @param string $name
     * @return mixed
     */

    public function downloadLabels($barcodes, $combine = true, $mode = 'I', $name = 'Omniva labels')
    {
        $result = $this->getLabels($barcodes);
        if (is_array($result['labels'])) {
            $pdf = new Fpdi();
            $label_count = 0;
            $print_type = $combine ? 4 : 1;
            foreach ($result['labels'] as $barcode => $pdf_data) {
                $stream = StreamReader::createByString(base64_decode($pdf_data));
                $page_count = $pdf->setSourceFile($stream);
                for ($i = 1; $i <= $page_count; $i++) {
                    $tplidx = $pdf->ImportPage($i);
                    if ($print_type == '1') {
                        $s = $pdf->getTemplatesize($tplidx);
                        $pdf->AddPage('P', array($s['width'], $s['height']));
                        $pdf->useTemplate($tplidx);
                    } else if ($print_type == '4') {
                        if ($label_count == 0 || $label_count == 4) {
                            $pdf->AddPage('P');
                            $label_count = 0;
                            $pdf->useTemplate($tplidx, 5, 15, 94.5, 108, false);
                        } else if ($label_count == 1) {
                            $pdf->useTemplate($tplidx, 110, 15, 94.5, 108, false);
                        } else if ($label_count == 2) {
                            $pdf->useTemplate($tplidx, 5, 160, 94.5, 108, false);
                        } else if ($label_count == 3) {
                            $pdf->useTemplate($tplidx, 110, 160, 94.5, 108, false);
                        }
                        $label_count++;
                    }
                }
            }
            if ($mode === 'S') {
                return $pdf->Output($mode, $name . '.pdf');
            }
            $pdf->Output($mode, $name . '.pdf');
        }
        return false;
    }

}
