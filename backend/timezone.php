<?php
/**
 * Configura��o de Timezone para todo o sistema
 * Garante que todas as datas e hor�rios usem o fuso hor�rio de S�o Paulo/Bras�lia
 * 
 * Este arquivo deve ser inclu�do no in�cio de todos os scripts PHP que manipulam datas/hor�rios.
 */

// Define timezone padr�o para todo o sistema: S�o Paulo/Bras�lia (UTC-3)
date_default_timezone_set('America/Sao_Paulo');

/**
 * Retorna o DateTime atual no timezone de Bras�lia
 * @return DateTime
 */
function getBrasiliaDateTime(): DateTime {
    return new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
}

/**
 * Retorna a data/hora atual no formato MySQL (Y-m-d H:i:s) no timezone de Bras�lia
 * @return string
 */
function getBrasiliaDateTimeString(): string {
    return (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d H:i:s');
}

/**
 * Converte uma string de data/hora para o timezone de Bras�lia
 * @param string $dateTimeString
 * @return DateTime
 */
function convertToBrasiliaDateTime(string $dateTimeString): DateTime {
    try {
        $dt = new DateTime($dateTimeString);
        $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        return $dt;
    } catch (Exception $e) {
        // Se falhar, retorna data/hora atual de Bras�lia
        return getBrasiliaDateTime();
    }
}

/**
 * Formata uma data/hora para exibi��o no padr�o brasileiro
 * @param string $dateTimeString
 * @param string $format (padr�o: 'd/m/Y H:i')
 * @return string
 */
function formatBrasiliaDateTime(string $dateTimeString, string $format = 'd/m/Y H:i'): string {
    try {
        $dt = convertToBrasiliaDateTime($dateTimeString);
        return $dt->format($format);
    } catch (Exception $e) {
        return $dateTimeString;
    }
}

