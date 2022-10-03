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
     * stocke dans un tableau les paramètres et renvoie une clef d'identification
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
     * Fonction qui attend les résultats de tout les curls
     */
    public function waitResult(): void;

    /**
     * @param string $clef
     * @return HttpResponse
     * Fonction qui retourne l'objet contenu du tableau de résultats en fonction de la clef
     */
    public function getResult(string $clef) :HttpResponse;


    /**
     * exécute une requête unique
     * @param string $url
     * @param array<string, mixed> $headers
     * @param string $methode
     * @param string $data
     * @return HttpResponse
     */
    public function curlUnique(string $url, array $headers, string $methode, string $data = ''):HttpResponse;

    /**
     * Cette fonction sert à indiquer que l'on suit la redirection
     */
    public function followRedirect():void;

    /**
     * défini un timeout pour les requêtes
     * @param int $timeout exprimé en seconde
     */
    public function setTimeout(int $timeout): void;
}
