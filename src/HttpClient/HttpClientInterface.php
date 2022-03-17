<?php

declare(strict_types=1);

namespace HttpClient;

/**
 * Interface ApiHttpClientInterface
 * Gere les appelles au api
 * @package Api\Utilities
 */
interface HttpClientInterface
{
    /**
     * stocke dans un tableaux les paramettre et renvoie une clef d'identification
     * @param string $url
     * @param array<string, mixed> $headers
     * @param string $methode
     * @param string $data
     * @return string
     */
    public function addParamRequest(string $url, array $headers, string $methode, string $data = ''):string;


    /**
     * Fonction qui execute tout les curls et met les retours dans un tableaux
     */
    public function execAll() : void;


    /**
     * Fonction qui attend les resultats de tout les curls
     */
    public function waitResult(): void;

    /**
     * @param string $clef
     * @return HttpResponse
     * Fonction qui retourne l'objet contenu du tableaux de résultat en fonction de la clef
     */
    public function getResult(string $clef) :HttpResponse;


    /**
     * @param string $url
     * @param array<string, mixed> $headers
     * @param string $methode
     * @param string $data
     * @return HttpResponse
     */
    public function curlUnique(string $url, array $headers, string $methode, string $data = ''):HttpResponse;

    /**
     * Cette fonction sert a indiqué que l'on suis la redirection
     */
    public function followRedirect():void;
}
