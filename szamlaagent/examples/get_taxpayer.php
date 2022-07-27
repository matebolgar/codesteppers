<?php
    /**
     * Ez a példa megmutatja, hogy hogyan kérdezzük le egy adózó adatait (név, cím) törzsszám alapján.
     *
     * Fontos! Az adatok a NAV-tól érkeznek. A NAV bármikor változtathat az interface-en,
     * illetve nem minden esetben adnak vissza címadatokat, így erre is fel kell készíteni a kódot.
     */
    require __DIR__ . '/autoload.php';

    use \SzamlaAgent\SzamlaAgentAPI;

    try {
        // Számla Agent létrehozása alapértelmezett adatokkal
        $agent = SzamlaAgentAPI::create('agentApiKey');
        // Adózó adatainak lekérdezése törzsszám (adószám első 8 számjegye) alapján
        $result = $agent->getTaxPayer('12345678');

        // Agent válasz sikerességének ellenőrzése
        if ($result->isSuccess()) {
            // A válasz adatai további feldolgozáshoz
            var_dump($result->getDataObj());
        }
    } catch (\Exception $e) {
        $agent->logError($e->getMessage());
    }