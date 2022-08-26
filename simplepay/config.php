<?php

/**
 *  Copyright (C) 2020 OTP Mobil Kft.
 *
 *  PHP version 7
 *
 *  This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  SDK
 * @package   SimplePayV2
 * @author    SimplePay IT Support <itsupport@otpmobil.com>
 * @copyright 2020 OTP Mobil Kft.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html  GNU GENERAL PUBLIC LICENSE (GPL V3.0)
 * @link      http://simplepartner.hu/online_fizetesi_szolgaltatas.html
 */

$config = [
  'EUR_MERCHANT' => $_SERVER['SIMPLEPAY_EUR_MERCHANT'],
  'EUR_SECRET_KEY' => $_SERVER['SIMPLEPAY_SECRET_KEY'],

  'SANDBOX' => (bool)$_SERVER['SIMPLEPAY_SANDBOX'],

  'GET_DATA' => (isset($_GET['r']) && isset($_GET['s'])) ? ['r' => $_GET['r'], 's' => $_GET['s']] : [],
  'POST_DATA' => $_POST,
  'SERVER_DATA' => $_SERVER,

  'LOGGER' => true,                              //basic transaction log
  'LOG_PATH' => __DIR__ . '/log',                //path of log file

  //3DS
  'AUTOCHALLENGE' => true,                      //in case of unsuccessful payment with registered card run automatic challange
];
