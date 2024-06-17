<?php

namespace Mijora\Omniva\Shipment;

use Mijora\Omniva\OmnivaException;

class Manifest
{

    /**
     * @var array
     */
    private $orders = [];
    
    /*
     * @var Contact
     */
    private $sender;

    /**
     * @var boolean
     */
    private $show_barcode = false;

    /**
     * @var array
     */
    private $strings = array(
        'sender_address' => 'Sender address',
        'row_number' => 'No.',
        'shipment_number' => 'Shipment number',
        'order_number' => 'Order no.',
        'date' => 'Date',
        'quantity' => 'Quantity',
        'weight' => 'Weight (kg)',
        'recipient_address' => "Recipient's address",
        'courier_signature' => 'Courier name, surname, signature',
        'sender_signature' => 'Sender name, surname, signature'
    );

    /**
     * @var array
     */
    private $column_lengths = array(
        'row_number' => 30,
        'shipment_number' => 80,
        'order_number' => 60,
        'date' => 50,
        'quantity' => 40,
        'weight' => 60,
        'recipient_address' => '',
    );

    /**
     * @var integer
     */
    private $signature_line_length = 48;

    /*
     * @param Order $order
     * @return Manifest
     */
    public function addOrder($order) {
        $this->orders[] = $order;
        return $this;
    }

    /*
     * @param Contact $sender
     * @return Manifest
     */
    public function setSender($sender) {
        $this->sender = $sender;
        return $this;
    }

    /*
     * @param boolean $show_barcode
     * @return Manifest
     */
    public function showBarcode($show_barcode) {
        $this->show_barcode = $show_barcode;
        return $this;
    }

    /*
     * @param string $key
     * @param string $string
     * @return Manifest
     */
    public function setString($key, $string) {
        $this->strings[$key] = $string;
        return $this;
    }

    /*
     * @param string $key
     * @param integer $length
     * @return Manifest
     */
    public function setColumnLength($key, $length) {
        $this->column_lengths[$key] = $length;
        return $this;
    }

    /*
     * @param integer $line_length
     * @return Manifest
     */
    public function setSignatureLineLength($line_length) {
        $this->signature_line_length = $line_length;
        return $this;
    }

    /*
     * $mode - I inline, D download, F save to file
     * @param string $mode
     * @param string $name
     * @return mixed
     */
    public function downloadManifest($mode = 'I', $name = 'Omniva manifest') {
        if ($this->show_barcode) {
            $this->setColumnLength('shipment_number', $this->column_lengths['shipment_number'] + 20);
        }

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $order_table = '';
        $count = 0;
        foreach ($this->orders as $order) {
            $count++;
            $cell_shipment_number = '<td width="' . $this->column_lengths['shipment_number'] . '">' . $order->getTracking() . '</td>';
            if ($this->show_barcode && defined('K_TCPDF_CALLS_IN_HTML') && K_TCPDF_CALLS_IN_HTML === true) {
                if (method_exists($pdf, 'serializeTCPDFtagParameters')) {
                    $cell_shipment_number = '<td width="' . $this->column_lengths['shipment_number'] . '" style="line-height: 50%;"><tcpdf method="write1DBarcode" params="' . $pdf->serializeTCPDFtagParameters($this->getBarcodeParams($order->getTracking())) . '" /></td>';
                } elseif (method_exists($pdf, 'serializeTCPDFtag')) {
                    $cell_shipment_number = '<td width="' . $this->column_lengths['shipment_number'] . '" style="line-height: 50%;"><tcpdf data="' . $pdf->serializeTCPDFtag('write1DBarcode', $this->getBarcodeParams($order->getTracking())) . '" /></td>';
                }
            }
            $order_table .= '<tr>
                <td width = "' . $this->column_lengths['row_number'] . '" align="right">' . $count . '.</td>
                '. $cell_shipment_number .'
                <td width = "' . $this->column_lengths['order_number'] . '">' . $order->getOrderNumber() . '</td>
                <td width = "' . $this->column_lengths['date'] . '">' . date('Y-m-d') . '</td>
                <td width = "' . $this->column_lengths['quantity'] . '">' . $order->getQuantity() . '</td>
                <td width = "' . $this->column_lengths['weight'] . '">' . $order->getWeight() . '</td>
                <td width = "' . $this->column_lengths['recipient_address'] . '">' . $order->getReceiver() . '</td>
            </tr>';
        }

        $pdf->SetFont('freeserif', '', 14);
        $shop_address = $this->sender->getAddress();
        $shop_addr = '<table cellspacing="0" cellpadding="1" border="0"><tr><td>' . date('Y-m-d H:i:s') . '</td><td>' . $this->strings['sender_address'] . ':<br/>' . $this->sender->getPersonName() . '<br/>' . $shop_address->getStreet() . ', ' . $shop_address->getPostCode() . '<br/>' . $shop_address->getDeliveryPoint() . ', ' . $shop_address->getCountry() . '<br/></td></tr></table>';

        $pdf->writeHTML($shop_addr, true, false, false, false, '');
        $tbl = '
        <table cellspacing="0" cellpadding="4" border="1">
          <thead>
            <tr>
              <th width = "' . $this->column_lengths['row_number'] . '" align="right">' . $this->strings['row_number'] . '</th>
              <th width = "' . $this->column_lengths['shipment_number'] . '">' . $this->strings['shipment_number'] . '</th>
              <th width = "' . $this->column_lengths['order_number'] . '">' . $this->strings['order_number'] . '</th>
              <th width = "' . $this->column_lengths['date'] . '">' . $this->strings['date'] . '</th>
              <th width = "' . $this->column_lengths['quantity'] . '">' . $this->strings['quantity'] . '</th>
              <th width = "' . $this->column_lengths['weight'] . '">' . $this->strings['weight'] . '</th>
              <th width = "' . $this->column_lengths['recipient_address'] . '">' . $this->strings['recipient_address'] . '</th>
            </tr>
          </thead>
          <tbody>
            ' . $order_table . '
          </tbody>
        </table><br/><br/>
        ';
        $pdf->SetFont('freeserif', '', 9);
        $pdf->writeHTML($tbl, true, false, false, false, '');
        $pdf->SetFont('freeserif', '', 14);
        $sign = $this->strings['courier_signature'] . ' ' . str_repeat('_', $this->signature_line_length) . '<br/><br/>';
        $sign .= $this->strings['sender_signature'] . ' ' . str_repeat('_', $this->signature_line_length);
        $pdf->writeHTML($sign, true, false, false, false, '');
        
        
        if ($mode === 'S') {
            return $pdf->Output($name . '.pdf', $mode);
        }

        $pdf->Output( $name . '.pdf', $mode);
    }

    private function getBarcodeParams($tracking_number) {
        return array($tracking_number, 'C128', '', '', 25, 6, 0.4, array('position'=>'C', 'border'=>false, 'padding'=>0, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N');
    }
}
