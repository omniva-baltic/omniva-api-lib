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

    /**
     * Dowloads or return a PDF of labels for the given barcodes
     * 
     * @param array $barcodes       Array of barcodes
     * @param bool $combine         If true, combines labels in a 2x2 grid; if false, one label per page
     * @param string $mode          Output mode: 'I' = inline, 'D' = download, 'F' = save to file, 'S' = return as string.
     * @param string $name          PDF file name (used for 'D', 'F', 'S' modes)
     * @param bool $use_legacy_api  If true, uses the legacy labels API
     * @return mixed                Returns PDF content as string if $mode = 'S', or false on failure
     */
    public function downloadLabels($barcodes, $combine = true, $mode = 'I', $name = 'Omniva labels', $use_legacy_api = false)
    {
        $result = $this->getLabels($barcodes, $use_legacy_api);

        if (!is_array($result['labels'])) {
            return false;
        }

        $pdf = new Fpdi();
        
        $label_count = 0;
        $print_type = $combine ? 4 : 1;
        $marginX = 5; // mm
        $marginY = 15; // mm
        $marginBetween = 5; // mm
        
        foreach ($result['labels'] as $barcode => $pdf_data) {
            $stream = StreamReader::createByString(base64_decode($pdf_data));
            $page_count = $pdf->setSourceFile($stream);
            for ($i = 1; $i <= $page_count; $i++) {
                $tplidx = $pdf->ImportPage($i);
                $s = $pdf->getTemplateSize($tplidx);

                if ($print_type == 1) {
                    $pdf->AddPage($s['orientation'], array($s['width'], $s['height']));
                    $pdf->useTemplate($tplidx);
                } else if ($print_type == 4) {
                    if ($label_count == 0) {
                        $pdf->AddPage('P');
                    }
                    $pageW = $pdf->GetPageWidth();
                    $pageH = $pdf->GetPageHeight();
                    $maxW = ($pageW - 2 * $marginX - $marginBetween) / 2;
                    $maxH = ($pageH - 2 * $marginY - $marginBetween) / 2;

                    $isA5Landscape = false;
                    $sRatio = $s['width'] / $s['height'];
                    if ($s['orientation'] == 'L' && $sRatio > 1.3 && $sRatio < 1.5 && $s['width'] >= $maxW * 2) {
                        // Set only if it is a very large label (at least twice the size of the allocated space) to keep the text legible
                        $isA5Landscape = true;
                    }

                    if ($isA5Landscape) {
                        $maxW = $pageW - 2 * $marginX;
                        if ($label_count == 1) { // Move to next row if it is the right column
                            $label_count++;
                        } else if ($label_count == 3) { // Add page if it is the right column
                            $pdf->AddPage('P');
                            $label_count = 0;
                        }
                    }

                    $scale = $maxW / $s['width'];
                    $w = $maxW;
                    $h = $s['height'] * $scale;
                    if ($h > $maxH) {
                        $scale = $maxH / $s['height'];
                        $h = $maxH;
                        $w = $s['width'] * $scale;
                    }

                    $position = [$marginX, $marginY];
                    if ($label_count == 1) {
                        $position = [$marginX + $maxW + $marginBetween, $marginY];
                    } else if ($label_count == 2) {
                        $position = [$marginX, $marginY + $maxH + $marginBetween];
                    } else if ($label_count == 3) {
                        $position = [$marginX + $maxW + $marginBetween, $marginY + $maxH + $marginBetween];
                    }

                    $pdf->useTemplate($tplidx, $position[0], $position[1], $w, $h, false);

                    if ($isA5Landscape) {
                        $label_count++; // 2 places were taken
                    }

                    $label_count++;
                    if ($label_count == 4) {
                        $label_count = 0;
                    }
                }
            }
        }
        
        if ($mode === 'S') {
            return $pdf->Output($mode, $name . '.pdf');
        }
        
        $pdf->Output($mode, $name . '.pdf');
    }
}
