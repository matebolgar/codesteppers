<?php
    /**
     * Ez a példa megmutatja, hogy hogyan töltsünk le egy számlát PDF-ben
     */
    require __DIR__ . '/../../autoload.php';

    use \SzamlaAgent\SzamlaAgentAPI;

    try {
        // Számla Agent létrehozása alapértelmezett adatokkal
        $agent = SzamlaAgentAPI::create('agentApiKey');

        /**
         * Számla PDF lekérdezése számlaszám vagy rendelésszám alapján
         *
         * Rendelésszám alapján való lekérdezés esetén a legutolsó bizonylatot adjuk vissza, amelyiknek ez a rendelésszáma.
         * @example $agent->getInvoicePdf('TESZT-001', Invoice::FROM_ORDER_NUMBER);
         */
        $result = $agent->getInvoicePdf('TESZT-001');

        // Agent válasz sikerességének ellenőrzése
        if ($result->isSuccess()) {
            $result->downloadPdf();
        }
    } catch (\Exception $e) {
        $agent->logError($e->getMessage());
    }