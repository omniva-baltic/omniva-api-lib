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
     * $mode - I inline, D download, F save to file
     * @param string $mode
     * @param string $name
     * @return mixed
     */
    public function downloadManifest($mode = 'I', $name = 'Omniva manifest') {

        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $order_table = '';
        $count = 0;
        foreach ($this->orders as $order) {
            $count++;
            $order_table .= '<tr><td width = "40" align="right">' . $count . '.</td><td>' . $order->getTracking() . '</td><td width = "60">' . date('Y-m-d') . '</td><td width = "40">' . $order->getQuantity() . '</td><td width = "60">' . $order->getWeight() . '</td><td width = "210">' . $order->getReceiver() . '</td></tr>';
        }

        $pdf->SetFont('freeserif', '', 14);
        $shop_address = $this->sender->getAddress();
        $shop_addr = '<table cellspacing="0" cellpadding="1" border="0"><tr><td>' . date('Y-m-d H:i:s') . '</td><td>' . 'Sender address' . ':<br/>' . $this->sender->getPersonName() . '<br/>' . $shop_address->getStreet() . ', ' . $shop_address->getPostCode() . '<br/>' . $shop_address->getDeliveryPoint() . ', ' . $shop_address->getCountry() . '<br/></td></tr></table>';

        $pdf->writeHTML($shop_addr, true, false, false, false, '');
        $tbl = '
        <table cellspacing="0" cellpadding="4" border="1">
          <thead>
            <tr>
              <th width = "40" align="right">' . 'No.' . '</th>
              <th>' . 'Shipment number' . '</th>
              <th width = "60">' . 'Date' . '</th>
              <th width = "40">' . 'Quantity' . '</th>
              <th width = "60">' . 'Weight (kg)' . '</th>
              <th width = "210">' . "Recipient's address" . '</th>
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
        $sign = "Courier name, surname, signature" . ' ________________________________________________<br/><br/>';
        $sign .= "Sender name, surname, signature" . ' ________________________________________________';
        $pdf->writeHTML($sign, true, false, false, false, '');
        
        
        if ($mode === 'S') {
            return $pdf->Output($name . '.pdf', $mode);
        }

        $pdf->Output( $name . '.pdf', $mode);
    }

}
