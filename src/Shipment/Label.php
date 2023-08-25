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

    public function setAuth($username, $password, $api_url = 'https://edixml.post.ee', $debug = false)
    {
        $this->request = new Request($username, $password, $api_url, $debug);
    }

    /**
     * @param array $barcodes Barcode or barcode array to get labels for
     * @param string|null $send_to_email If email is set labels will be sent to that email. OMX API only.
     * @param bool $use_legacy_api Should call be made using legacy api instead of OMX API. Default is FALSE
     * 
     * @return mixed
     * 
     * @throws OmnivaException
     */
    public function getLabels($barcodes, $send_to_email = null, $use_legacy_api = false)
    {
        if (!is_array($barcodes)) {
            $barcodes = [$barcodes];
        }

        if (empty($this->request)) {
            throw new OmnivaException("Please set username and password");
        }

        $result = $use_legacy_api ? $this->request->getLabels($barcodes) : $this->request->getLabelsOmx($barcodes, $send_to_email);

        return $result;
    }

    /**
     * Sends given barcodes labels to set email. OMX API only.
     * 
     * @param array|string $barcodes
     * @param string $email
     * 
     * @return mixed
     * 
     * @throws OmnivaException
     */
    public function sendLabelsToEmail($barcodes, $email)
    {
        return $this->getLabels($barcodes, $email);
    }

    /*
     * $mode - I inline, D download, F save to file, S - return string
     * @param array $barcodes
     * @param bool $combine
     * @param string $mode
     * @param string $name
     * @return mixed
     */

    public function downloadLabels($barcodes, $combine = true, $mode = 'I', $name = 'Omniva labels', $use_legacy_api = false)
    {
        $result = $this->getLabels($barcodes, $use_legacy_api);
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
