<?php

namespace CodeSteppers;

class Invoice
{
    public static function sendInvoice($name, $taxNumber, $zip, $city, $address, $email, $productName, $price)
    {
        require_once('szamlaagent/examples/autoload.php');

        try {
          
            $agent = \SzamlaAgent\SzamlaAgentAPI::create($_SERVER['SZAMLAAGENT_API_KEY']);
            $agent->setLogEmail($_SERVER['SZAMLAAGENT_LOG_EMAIL']);      
            $invoice = new \SzamlaAgent\Document\Invoice\Invoice(\SzamlaAgent\Document\Invoice\Invoice::INVOICE_TYPE_E_INVOICE);
            
            $header = $invoice->getHeader();
            $header->setLanguage("en");
          
            $header->setPaymentMethod(\SzamlaAgent\Document\Invoice\Invoice::PAYMENT_METHOD_BANKCARD);
            $header->setCurrency('EUR');

            $header->setPaid(true);
            // Számla teljesítés dátuma
            $header->setFulfillment(date('Y-m-d'));
            // Számla fizetési határideje
            $header->setPaymentDue(date('Y-m-d'));

            $buyer = new \SzamlaAgent\Buyer($name, (string)$zip, $city, $address);
            $buyer->setTaxNumber($taxNumber);
            $buyer->setEmail($email);
            $buyer->setSendEmail(true);
            $invoice->setBuyer($buyer);


            // Eladó létrehozása
            $seller = new \SzamlaAgent\Seller();
            $seller->setEmailSubject('CodeSteppers Invoice');
            $seller->setEmailReplyTo($_SERVER['SZAMLAAGENT_LOG_EMAIL']);
            $seller->setEmailContent('Thank you for the purchase!');
            $invoice->setSeller($seller);


            // Számla tétel összeállítása alapértelmezett adatokkal (1 db tétel 27%-os ÁFA tartalommal)
            $item = new \SzamlaAgent\Item\InvoiceItem($productName, $price);


            // Tétel nettó értéke
            $item->setNetPrice($price);

            // Tétel ÁFA értéke
            $item->setVatAmount(0);
            $item->setVat('AAM');

            // Tétel bruttó értéke
            $item->setGrossAmount($price);

            // Tétel hozzáadása a számlához
            $invoice->addItem($item);

            // Számla elkészítése
            $result = $agent->generateInvoice($invoice);
            // Agent válasz sikerességének ellenőrzése
            if ($result->isSuccess()) {
                return true;
            }
        } catch (\Exception $e) {
            $agent->logError($e->getMessage());
            echo "<pre>";
            var_dump($e);
        }
    }

    public static function sendReceipt($email, $productName, $price, $ref)
    {
        require_once('szamlaagent/examples/autoload.php');
        try {
            $agent = \SzamlaAgent\SzamlaAgentAPI::create($_SERVER['SZAMLAAGENT_API_KEY']);
            $agent->setLogEmail($_SERVER['SZAMLAAGENT_LOG_EMAIL']);

            $receipt = new \SzamlaAgent\Document\Receipt\Receipt();

            $header = new \SzamlaAgent\Header\ReceiptHeader();
            $header->setPaymentMethod('bankcard');
            $header->setCurrency('EUR');

            $header->setComment($ref);

            $receipt->setHeader($header);
            // Nyugta előtag beállítása
            $receipt->getHeader()->setPrefix($_SERVER['SZAMLAAGENT_RECEIPT_PREFIX']);
            // Nyugta tétel összeállítása (1 db eladó tétel 27%-os ÁFA tartalommal)
            $item = new \SzamlaAgent\Item\ReceiptItem($productName, $price);


            // Tétel nettó értéke
            $item->setNetPrice($price);

            // Tétel ÁFA értéke
            $item->setVatAmount(0);
            $item->setVat('AAM');

            // Tétel bruttó értéke
            $item->setGrossAmount($price);

            // Tétel hozzáadása a nyugtához
            $receipt->addItem($item);

            // Nyugta elküldése
            $result = $agent->generateReceipt($receipt);

            // Agent válasz sikerességének ellenőrzése
            if ($result->isSuccess()) {
                $receipt = new \SzamlaAgent\Document\Receipt\Receipt($result->getDocumentNumber());
                // Vevő létrehozása
                $buyer = new \SzamlaAgent\Buyer();
                // Vevő e-mail címe (ide megy ki a levél)
                $buyer->setEmail($email);
                // Vevői adatok hozzáadása a nyugtához
                $receipt->setBuyer($buyer);

                // Eladó e-mail értesítő beállítása
                $seller = new \SzamlaAgent\Seller();
                // Ha a vevő válaszol, erre a címre érkezik be a válasz
                $seller->setEmailReplyTo($_SERVER['SZAMLAAGENT_LOG_EMAIL']);
                $seller->setEmailSubject('CodeSteppers Receipt');

                $seller->setEmailContent('Thank you for the purchase!');
                // Eladói adatok hozzáadása a nyugtához
                $receipt->setSeller($seller);

                $agent->sendReceipt($receipt);
            }
        } catch (\Exception $e) {
            $agent->logError($e->getMessage());
        }
    }
}
