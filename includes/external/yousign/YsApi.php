<?php

namespace YousignAPI;

class YsApi
{
    const API_NAMESPACE = 'http://www.yousign.com';

    const API_ENV_DEMO = 'demo';
    const API_ENV_PROD = 'prod';

    const API_URL_DEMO = 'https://apidemo.yousign.fr:8181/';
    const API_URL_PROD = 'https://api.yousign.fr:8181/';

    const IFRAME_URL_DEMO = 'https://demo.yousign.fr';
    const IFRAME_URL_PROD = 'https://yousign.fr';

    /**
     * Constantes sur la protection d'un fichier
     */
    const DOCUMENT_NOT_LOCKED   = 'not_locked';
    const DOCUMENT_NEED_PASSWD  = 'need_passwd';
    const DOCUMENT_IS_LOCKED    = 'is_locked';

    /**
     * URL d'accès au WSDL de l'API Yousign d'authentification.
     *
     * @var string
     */
    private $URL_WSDL_AUTH = '';

    /**
     * URL d'accès au WSDL de l'API Yousign de Co-signature.
     *
     * @var string
     */
    private $URL_WSDL_COSIGN = '';

    /**
     * URL d'accès au WSDL de l'API Yousign d'archivage.
     *
     * @var string
     */
    private $URL_WSDL_ARCHIVE = '';

    /**
     * Contient le login de connexion au web service de l'utilisateur courant.
     *
     * @var string
     */
    private $_login = '';

    /**
     * Contient le mot de passe de connexion au web service en sha1.
     *
     * @var string
     */
    private $_password = '';

    /**
     * La clé d'API.
     *
     * @var string
     */
    private $apikey = '';

    /**
     * Url d'accès à l'API.
     *
     * @var string
     */
    private $urlApi = '';

    /**
     * URL d'accès à l'Iframe.
     *
     * @var string
     */
    private $urlIframe = '';

    /**
     * Définit l'utilisation ou non du protocol SSL.
     *
     * @var bool
     */
    private $enabledSSL = false;

    /**
     * Emplacement du keystore client (SSL doit être actif).
     *
     * @var string
     */
    private $certClientLocation = '';

    /**
     * Emplacement de la chaine de certification (SSL doit être actif).
     *
     * @var string
     */
    private $caChainClientLocation = '';

    /**
     * Emplacement de la clef privée client  (SSL doit être actif).
     *
     * @var string
     */
    private $privateKeyClientLocation = '';

    /**
     * Mot de passe de la clef privée client  (SSL doit être actif).
     *
     * @var string
     */
    private $privateKeyClientPassword = '';

    /**
     * Définit si l'utilisateur est bien authentifié ou non.
     *
     * @var bool
     */
    private $isAuthenticated = false;

    /**
     * Contient les paramètres d'accès à l'api pki.
     *
     * @var array
     */
    private $parameters = null;

    /**
     * Gestion des erreurs.
     *
     * @var array
     */
    private $errors = array();

    /**
     * Instance du client.
     *
     * @var \nusoap_client
     */
    private $client;

    /**
     * Permet de créer une nouvelle instance de la classe ysApi.
     *
     * Il est possible de passer en paramètre le chemin d'un fichier properties (.ini)
     *
     * Si c'est le cas, il peut contenir les clés suivantes :
     *     - url_api    : L'url d'accès à l'API
     *     - login      : L'identifiant Yousign (adresse email)
     *     - password   : Mot de passe Yousign
     *     - api_key    : La clé d'API
     *
     * @param null $pathParametersFile : Chemin de configuration
     */
    public function __construct($pathParametersFile = null)
    {
        if ($pathParametersFile !== null && file_exists($pathParametersFile)) {
            $this->parseParametersFile($pathParametersFile);
        }
    }

    /**
     * Modifie l'environnement de l'API utilisé. (env|prod)
     *
     * @param $environment
     * @return $this
     */
    public function setEnvironment($environment)
    {
        switch ($environment) {
            // Environnement de production
            case self::API_ENV_PROD:
                $this->setUrlIframe(self::IFRAME_URL_PROD);
                $this->setUrlApi(self::API_URL_PROD);

                return $this;

            // Par défaut, environnement de démo
            case self::API_URL_DEMO:
            default:
                $this->setUrlIframe(self::IFRAME_URL_DEMO);
                $this->setUrlApi(self::API_URL_DEMO);

                return $this;
        }
    }

    /**
     * Modifie l'url d'accès à l'API.
     *
     * @param $urlApi
     * @return $this
     */
    public function setUrlApi($urlApi)
    {
        $this->urlApi = $urlApi;

        // On créé les adresses des wsdl
        $this->URL_WSDL_AUTH = $this->urlApi.'/AuthenticationWS/AuthenticationWS?wsdl';
        $this->URL_WSDL_COSIGN = $this->urlApi.'/CosignWS/CosignWS?wsdl';
        $this->URL_WSDL_ARCHIVE = $this->urlApi.'/ArchiveWS/ArchiveWS?wsdl';

        return $this;
    }

    /**
     * Modifie l'URL d'accès à l'Iframe.
     *
     * @param $urlIframe
     * @return $this
     */
    public function setUrlIframe($urlIframe)
    {
        $this->urlIframe = $urlIframe;

        return $this;
    }

    /**
     * Modification de l'identifiant d'accès à l'API.
     *
     * @param $login
     * @return $this
     */
    public function setLogin($login)
    {
        $this->_login = $login;

        return $this;
    }

    /**
     * Modification du mot de passe d'accès à l'API.
     *
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->_password = $password;

        return $this;
    }

    /**
     * Modification de la clé d'API Yousign.
     *
     * @param $apikey
     * @return $this
     */
    public function setApiKey($apikey)
    {
        $this->apikey = $apikey;

        return $this;
    }

    /**
     * Active ou non l'utilisation de SSL.
     *
     * @param $enabled
     * @return $this
     */
    public function setEnabledSSL($enabled)
    {
        $this->enabledSSL = $enabled;

        return $this;
    }

    /**
     * Modification de l'emplacement de la chaine de certification.
     *
     * @param $ca_chain_client_location
     * @return $this
     */
    public function setCaChainClientLocation($ca_chain_client_location)
    {
        $this->caChainClientLocation = $ca_chain_client_location;

        return $this;
    }

    /**
     * Modification de l'emplacement de la clef privée client  (SSL doit être actif).
     *
     * @param $privateKeyClientLocation
     * @return $this
     */
    public function setPrivateKeyClientLocation($privateKeyClientLocation)
    {
        $this->privateKeyClientLocation = $privateKeyClientLocation;

        return $this;
    }

    /**
     * Modification du mot de passe de la clef privée client  (SSL doit être actif).
     *
     * @param $privateKeyClientPassword
     * @return $this
     */
    public function setPrivateKeyClientPassword($privateKeyClientPassword)
    {
        $this->privateKeyClientPassword = $privateKeyClientPassword;

        return $this;
    }

    /**
     * Modification de l'emplacement du keystore client (SSL doit être actif).
     *
     * @param $certClientLocation
     * @return $this
     */
    public function setCertClientLocation($certClientLocation)
    {
        $this->certClientLocation = $certClientLocation;

        return $this;
    }

    /**
     * Cryptage du mot de passe.
     *
     * @param $password
     * @return string
     */
    public function encryptPassword($password)
    {
        return sha1(sha1($password).sha1($password));
    }

    /**
     * Retourne l'url d'accès à l'iframe de signature du document.
     *
     * @param string $token
     * @return string
     */
    public function getIframeUrl($token = '')
    {
        return $this->urlIframe.'/public/ext/cosignature/'.$token;
    }

    /**
     * Retourne les erreurs retournées par l'API.
     *
     * @return array
     */
    public function getErrors()
    {
        if (false === $this->errors) {
            $this->errors = array();
        }

        if (!is_array($this->errors)) {
            $this->errors = array($this->errors);
        }

        return $this->errors;
    }

    /**
     * Retourne l'instance du client soap utilisé.
     *
     * @return \SoapClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Connexion à l'API.
     *
     * @return bool
     */
    public function connect()
    {
        // Paramètre du service de connexion
        //@todo : supprimer ce paramètre ??
        $params = array(
            'arg0' => '1',
        );

        $this->client = $this->setClientSoap($this->URL_WSDL_AUTH);
        $result = $this->client->call('connect', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders());

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;

            return false;
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return $err;
            } else {
                if ($result == 'true') {
                    $this->isAuthenticated = true;

                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * Fonction permettant d'obtenir des informations sur une demande de cosignature spécifique.
     *
     * @param int $idDemand Id de la demande de cosignature
     * @return mixed Retourne :
     *               - DEMAND_NOT_ALLOWED si l'id de la demande passé est incorrect
     *               - false si une erreur est survenue
     *               - Tableau contenant les informations de la demande de cosignature
     *               - dateCreation : Date de creation de la demande de signature
     *               - description : Description de la demande de signature
     *               - status : Status global de la demande de signature
     *               - fileInfos : Tableau contenant la liste des informations des fichiers à signer/signés
     *                   * idFile : Id du fichier
     *                   * fileName : Nom du fichier
     *                   * cosignersWithStatus : Status de la signature pour chacun des signataires
     *                       + id : Id du signataire
     *                       + status : Status de la signature
     *               - cosignerInfos : Tableau contenant la liste des informations du/des signataires
     *                   * firstName : Prénom
     *                   * lastName : Nom
     *                   * mail : Email
     *                   * proofLevel : Niveau de preuve de la signature
     *                   * isCosignerCalling : True si le token est associé au signataire, false sinon
     *                   * id : Id du signataire
     *                   * phone : Numéro de téléphone du signataire
     *               - initiator : Tableau contenant les informations de l'initiateur de la demande de signature
     *                   * name : Nom + Prénom
     *                   * email : Email
     */
    public function getCosignInfoFromIdDemand($idDemand)
    {
        return $this->getCosignInfo(array('idDemand' => $idDemand));
    }

    /**
     * Fonction permettant de récupérer les informations d'une demande de co-signature en fonction du token.
     *
     * @param string $token : Token unique associé à une demande de cosignature
     * @return mixed Retourne :
     *               - INVALID_TOKEN si le token passé est incorrect
     *               - false si une erreur est survenue
     *               - Tableau contenant les informations de la demande de cosignature
     *               - dateCreation : Date de creation de la demande de signature
     *               - description : Description de la demande de signature
     *               - status : Status global de la demande de signature
     *               - fileInfos : Tableau contenant la liste des informations des fichiers à signer/signés
     *                   * idFile : Id du fichier
     *                   * fileName : Nom du fichier
     *                   * cosignersWithStatus : Status de la signature pour chacun des signataires
     *                       + id : Id du signataire
     *                       + status : Status de la signature
     *               - cosignerInfos : Tableau contenant la liste des informations du/des signataires
     *                   * firstName : Prénom
     *                   * lastName : Nom
     *                   * mail : Email
     *                   * proofLevel : Niveau de preuve de la signature
     *                   * isCosignerCalling :
     *                   * id : Id du signataire
     *                   * phone : Numéro de téléphone du signataire
     *               - initiator : Tableau contenant les informations de l'initiateur de la demande de signature
     *                   * name : Nom + Prénom
     *                   * email : Email
     */
    public function getCosignInfoFromToken($token)
    {
        return $this->getCosignInfo(array('token' => $token), false);
    }

    /**
     * Fonction permettant de récupérer un fichier signé d'une cosignature.
     *
     * @param int $idDemand : Id de la demande de cosignature
     * @param int $idFile   : Id du fichier signé à récupérer
     * @return mixed Retourne :
     *               - DEMAND_NOT_ALLOWED si la demande n'est pas associée à l'utilisateur
     *               - DEMAND_NOT_CONFIRMED si la demande n'a pas été validée
     *               - false si une erreur est survenue
     *               - un tableau contenant:
     *                   -> fileName : Le nom du fichier
     *                   -> file : Le fichier signé encodé en base64
     */
    public function getCosignedFileFromIdDemand($idDemand, $idFile)
    {
        return $this->getCosignedFile(array('idDemand' => $idDemand, 'idFile' => $idFile));
    }

    /**
     * Fonction permettant de récupérer un fichier signer.
     *
     * @param string $token  : Token unique associé à un signataire non enregistré
     * @param int    $idFile : Id du fichier signé à récupérer
     * @return mixed Retourne :
     *               - DEMAND_NOT_ALLOWED si la demande n'est pas associée à l'utilisateur
     *               - DEMAND_NOT_CONFIRMED si la demande n'a pas été validée
     *               - false si une erreur est survenue
     *               - un tableau contenant:
     *                   -> fileName : Le nom du fichier
     *                   -> file : Le fichier signé encodé en base64
     */
    public function getCosignedFileFromToken($token, $idFile)
    {
        return $this->getCosignedFile(array('token' => $token, 'idFile' => $idFile), false);
    }

    /**
     * Cette méthode est utilisée pour initialiser une demande de cosignature.
     * Vous passerez en paramètre une liste de fichiers à signer ainsi qu'une liste d'informations des cosignataires.
     *
     * Ils recevront ensuite un email contenant une URL unique pour accéder à l'interface de signature du/des documents afin de le/les signer.
     *
     * example:
     * ----------
     *     $listFiles = array(
     *         array(
     *             'name' => 'Fichier 1',
     *             'content' => 'base64 du fichier',
     *             'idFile' => 'idFile1'
     *         ),
     *         array(
     *             'name' => 'Fichier 2',
     *             'content' => 'base64 du fichier',
     *             'idFile' => 'idFile2'
     *         ),
     *     );
     *
     *     $lstPersons = array
     *     (
     *         array(
     *             'firstName' => 'Prenom 1',
     *             'lastName' => 'Nom 1',
     *             'mail' => 'prenom.nom1@mail.com',
     *             'phone' => '+33123456789',
     *             'proofLevel' => 'LOW'
     *         ),
     *         array(
     *             'firstName' => 'Prenom 1',
     *             'lastName' => 'Nom 1',
     *             'mail' => 'prenom.nom2@mail.com',
     *             'phone' => '+33123456789',
     *             'proofLevel' => 'LOW'
     *         ),
     *     );
     *
     *     $visibleOptions = array
     *     (
     *         $listFiles[0]['idFile'] => array
     *         (
     *             array(
     *                 'visibleSignaturePage' => '1',
     *                 'isVisibleSignature' => true,
     *                 'visibleRectangleSignature' => 'llx,lly,urx,ury',
     *                 'mail' => 'prenom.nom1@mail.com'
     *             ),
     *             array(
     *                 'visibleSignaturePage' => '1',
     *                 'isVisibleSignature' => true,
     *                 'visibleRectangleSignature' => 'llx,lly,urx,ury',
     *                 'mail' => 'prenom.nom2@mail.com'
     *             ),
     *         ),
     *         $listFiles[1]['idFile'] => array
     *         (
     *             array(
     *                 'visibleSignaturePage' => '3',
     *                 'isVisibleSignature' => true,
     *                 'visibleRectangleSignature' => 'llx,lly,urx,ury',
     *                 'mail' => 'prenom.nom1@mail.com'
     *             ),
     *             array(
     *                 'visibleSignaturePage' => '3',
     *                 'isVisibleSignature' => true,
     *                 'visibleRectangleSignature' => 'llx,lly,urx,ury',
     *                 'mail' => 'prenom.nom2@mail.com'
     *             ),
     *         )
     *     );
     *
     *     $message = 'Un message';
     *
     *     $options = array(
     *         'initMailSubject' => 'Sujet de l\'email',
     *         'initMail' => 'Contenu de l\'email'
     *         [...]
     *     );
     *
     * @param array  $lstFiles       : Liste du/des fichiers à signer, chaque fichier doit définir:
     *                                   - name : Nom du fichier à signer
     *                                   - content : Contenu du fichier à signer encodé en base64
     *                                   - idFile : identifiant unique (entier ou chaine de caractère)
     * @param array  $lstPersons     : Liste des cosignataires, chaque cosignataire doit définir:
     *                                   - firstName : Le prénom du cosignataire
     *                                   - lastName : Le nom du cosignataire
     *                                   - mail : L'email du cosignataire (ou un id si c'est en mode Iframe)
     *                                   - phone : Le numéro de téléphone du cosignataire (indicatif requis, exemple: +33612326554)
     *                                   - proofLevel : Niveau de preuve
     *                                       Disponible: LOW
     *                                       Par défaut: Rien
     * @param array  $visibleOptions : Liste d'informations requis pour le placement des signatures
     *                                   - visibleSignaturePage : Numéro de la page contenant les signatures
     *                                   - isVisibleSignature : Affiche ou non la signature sur le document
     *                                   - visibleRectangleSignature : Les coordonnées de l'image de signature (ignoré si "isVisibleSignature" est à false)
     *                                     Le format est "llx,lly,urx,ury" avec:
     *                                         * llx: left lower x coordinate
     *                                         * lly: left lower y coordinate
     *                                         * urx: upper right x coordinate
     *                                         * ury: upper right y coordinate
     *                                   - mail : Email du cosignataire associée à la signature
     * @param string $message        : Message de l'email qui sera envoyé aux cosignataires (Non utilisé si initMailXXX définis)
     * @param array  $options        : Tableau d'options facultatifs
     *                                   - initMailSubject : Sujet de l'email envoyé à tous les cosignataires à la création de la cosignature (Non utilisé en mode Iframe)
     *                                   - initMail : Corps de l'email envoyé à tous les cosignataires à la création de la cosignature.
     *                                     Il doit être en HTML et contenir la balise {yousignUrl} qui sera remplacée par l'URL
     *                                     d'accès à l'interface de signature du/des documents. (Non utilisé en mode Iframe)
     *                                   - endMailSubject : Sujet de l'email envoyé lorsque tous les cosignataires ont signés le/les documents (Non utilisé en mode Iframe)
     *                                   - endMail : Corps de l'email envoyé lorsque tous les cosignataires ont signés le/les documents
     *                                     Il dit être en HTML et contenir la balise {yousignUrl} qui sera remplacée par l'URL
     *                                     d'accès à l'interface listant le/les documents signés (Non utilisé en mode Iframe)
     *                                   - language : Langue définie pour la cosignature.
     *                                     Disponibles: FR|EN|DE
     *                                     Par défaut: FR
     *                                   - mode :     Mode d'utilisation (Aucun par défaut)
     *                                       * IFRAME : Permet de signer directement dans l'application hébergeant l'iframe
     *                                         Ceci retournera un token pour chaque signataire
     *                                         L'URL devant appeler l'Iframe est:
     *                                             => (Démo) https://demo.yousign.fr/public/ext/cosignature/{token}
     *                                             => (Prod) https://yousign.fr/public/ext/cosignature/{token}
     *                                   - archive : Booléen permettant d'activer l'archivage du/des documents signés automatiquement
     *                                     L'archivage se fait lorsque tous les cosignataires ont signés
     *
     * @return mixed : Id de la demande de cosignature créée et liste des id des fichiers à signer
     *               Si le mode "IFRAME" est définie, un token sera également retournée pour chaque cosignataire
     *               Pour associer le bon token au bon cosignataire, un email et un numéro de téléphone sont associés à chaque token
     *               (ou false si une erreur est survenue)
     *
     * @category com.yousign.cosignejb
     *
     * @link http://developer.yousign.fr/com/yousign/cosignejb/CosignWS.html#CosignWS()
     */
    public function initCoSign($lstFiles, $lstPersons, $visibleOptions, $message, $options = array())
    {
        $payload = '';

        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);

        // A cause d'un soucis dans la librairie Nusoap, nous sommes obligés de créer nous même le payload afin qu'il corresponde à notre WSDL.
        // Le pb : Avec un tableau de paramètres, Nusoap créer cette arborescence : <files><item>...</item><item>...</item></files>
        // Nous avons besoin de cette arborescence : <files>...</files><files>...</files>. Les lignes suivantes permettent de bien la construire.

        // Liste des fichiers de la co-signature
        foreach ($lstFiles as $key => $file) {
            $filePayload = '';
            $visibleOptionsPayload = '';

            // On récupère les options de signature visible
            foreach ($visibleOptions[$file['idFile']] as $option) {
                $item = array('visibleOptions' => $option);
                foreach ($item as $k => $v) {
                    $visibleOptionsPayload .= $this->client->serialize_val($v, $k, false, false, false, false, 'encoded');
                }
            }

            // On supprime l'id file qui est inutile
            unset($file['idFile']);

            $item = array('lstCosignedFile' => $file);
            foreach ($item as $k => $v) {
                $filePayload .= $this->client->serialize_val($v, $k, false, false, false, false, 'encoded');
            }

            // On insert les données de signature visible
            $payload .= substr_replace($filePayload, $visibleOptionsPayload, strpos($filePayload, '</lstCosignedFile>'), 0);
        }

        // Liste des co-signataires
        foreach ($lstPersons as $person) {
            $item = array('lstCosignerInfos' => $person);
            foreach ($item as $k => $v) {
                $payload .= $this->client->serialize_val($v, $k, false, false, false, false, 'encoded');
            }
        }

        // Ajout du message
        $payload .= $this->client->serialize_val($message, 'message', false, false, false, false, 'encoded');

        // Envoi d'email
        if (isset($options['initMailSubject']) && isset($options['initMail'])) {
            $payload .= $this->client->serialize_val($options['initMailSubject'], 'initMailSubject', false, false, false, false, 'encoded');
            $payload .= $this->client->serialize_val($options['initMail'], 'initMail', false, false, false, false, 'encoded');
        }

        if (isset($options['endMailSubject']) && isset($options['endMail'])) {
            $payload .= $this->client->serialize_val($options['endMailSubject'], 'endMailSubject', false, false, false, false, 'encoded');
            $payload .= $this->client->serialize_val($options['endMail'], 'endMail', false, false, false, false, 'encoded');
        }

        if (isset($options['language'])) {
            $payload .= $this->client->serialize_val($options['language'], 'language', false, false, false, false, 'encoded');
        }

        if (isset($options['mode'])) {
            $payload .= $this->client->serialize_val($options['mode'], 'mode', false, false, false, false, 'encoded');
        }

        if (isset($options['archive'])) {
            $payload .= $this->client->serialize_val($options['archive'], 'archive', false, false, false, false, 'encoded');
        }

        $result = $this->client->call('initCosign', $payload, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders());

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;

            return false;
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return false;
            } else {
                if ($result > 0) {
                    return $result;
                } else {
                    $this->errors[] = 'No result';

                    return false;
                }
            }
        }
    }

    /**
     * fonction permettant d'obtenir le listing des co-signatures.
     *
     * @param array $options
     *                       - [ search: Chaine de caractères permettant une recherche ]
     *                       - [ firstResult: index de début de recherche ]
     *                       - [ count: Nombre de résultats voulu ]
     *                       - [ status: Statut des signatures demandés ]
     *                       - [ dateBegin: Date de début ]
     *                       - [ dateEnd: Date de fin ]
     *
     * @return array contenant pour chaque item
     *               - cosignatureEvent : Id de la demande de cosignature
     *               - dateCreation : Date de creation de la demande de signature
     *               - status : Status global de la demande de signature
     *               - fileInfos : Tableau contenant la liste des informations des fichiers à signer/signés
     *                   * idFile : Id du fichier
     *                   * fileName : Nom du fichier
     *                   * cosignersWithStatus : Status de la signature pour chacun des signataires
     *                       + id : Id du signataire
     *                       + status : Status de la signature
     *               - cosignerInfos : Tableau contenant la liste des informations du/des signataires
     *                   * firstName : Prénom
     *                   * lastName : Nom
     *                   * mail : Email
     *               - initiator : Tableau contenant les informations de l'initiateur de la demande de signature
     *                   * name : Nom + Prénom
     *                   * email : Email
     */
    public function getListCosign(array $options = array())
    {
        // Paramètre du service de connexion
        $params = $options;
        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);
        $result = $this->client->call('getListCosign', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders());

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;

            return false;
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return $err;
            } else {
                // Si aucun résultat, une chaine vide est retournée, on la transforme en tableau vide
                if (empty($result)) {
                    $result = array();
                }

                // Si un seul résultat, on l'englobe dans un tableau
                if (isset($result['status'])) {
                    $result = array($result);
                }

                return $result;
            }
        }
    }

    /**
     * Permet de supprimer une co-signature.
     *
     * @param $idDemand : Id de la demande de cosignature à supprimer
     * @return bool
     */
    public function deleteCosignDemand($idDemand)
    {
        // Paramètre du service de connexion
        $params = array('idDemand' => $idDemand);

        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);
        $result = $this->client->call('cancelCosignatureDemand', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders());

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;

            return false;
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return false;
            } else {
                if ($result == false || $result == 'false') {
                    return false;
                }

                return $result;
            }
        }
    }

    /**
     * Permet de relancer les co-signataires n'ayant pas signé lors d'une demande de cosignature.
     *
     * @param $idDemand : Id de la demande de cosignature
     * @return bool
     */
    public function alertCosigners($idDemand)
    {
        // Paramètre du service de connexion
        $params = array(
                        'idDemand' => $idDemand,
                        );

        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);
        $result = $this->client->call('alertCosigners', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders());

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;

            return false;
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return false;
            } else {
                if ($result == false || $result == 'false') {
                    return false;
                }

                return $result;
            }
        }
    }

    /**
     * Vérifie si un pdf nécessite un mot de passe ou non
     * Et si ce dernier est signable ou non
     *
     * @param $pathfile
     * @param string $password
     * @return bool|mixed
     * @throws \Exception
     */
    public function isPDFSignable($pathfile, $password = '')
    {
        if(!file_exists($pathfile)) {
            throw new \Exception('File not found');
        }

        $params = array (
            'pdfFile' => base64_encode(file_get_contents($pathfile)),
            'pdfPassword' => $password
        );

        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);
        $this->client->call('isPDFSignable', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders(false));

        $this->errors = array();
        if($this->client->fault)
        {
            if(preg_match('/Error 100 :/i', $this->client->faultstring))
                return self::DOCUMENT_NEED_PASSWD;

            if(preg_match('/Error 101 :/i', $this->client->faultstring))
                return self::DOCUMENT_IS_LOCKED;

            $this->errors[] = $this->client->faultstring;
            return false;
        }

        return self::DOCUMENT_NOT_LOCKED;
    }

    /**
     * Modifie les informations d'un signataire
     *
     * @param $token
     * @param array $signerData (firstName, lastName, mail, phone, proofLevel, authenticationMode)
     * @return bool|mixed
     */
    public function updateCosigner($token, array $signerData)
    {
        $expectedData = array_intersect_key($signerData, array(
            'firstName' => '',
            'lastName' => '',
            'mail' => '',
            'phone' => '',
            'proofLevel' => '',
            'authenticationMode' => ''
        ));

        if(count($expectedData) === 0) {
            throw new \Exception('Data signer are empty or not correct');
        }

        $params = array (
            'token' => $token,
            'cosignerInfos' => $expectedData,
        );

        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);
        $result = $this->client->call('updateCosigner', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders(true));

        $this->errors = array();
        if($this->client->fault) {
            $this->errors[] = $this->client->faultstring;
            return false;
        }

        return $result;
    }

    /**
     * Permet d'ajouter pour un signataire et un document, un texte obligatoire à rédiger par le signataire
     *
     * @param $token
     * @param $idFile
     * @param $contentText
     * @param bool|true $status
     */
    public function addTextToWrite($token, $idFile, $contentText, $status = true)
    {
        $params = array (
            'token' => $token,
            'idFile' => (int) $idFile,
            'text' => $contentText,
            'status' => $status
        );

        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);
        $result = $this->client->call('addTextToWrite', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders(false));

        $this->errors = array();
        if($this->client->fault) {
            $this->errors[] = $this->client->faultstring;
            return false;
        }

        return $result;
    }

    /**
     * Retourne l'état d'authentification de l'utilisateur courant.
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }

    /***********************************************************
     * WEB SERVICES D'ARCHIVAGE
     ***********************************************************/

    /**
     * Fonction permettant d'archiver un ensemble de documents.
     *
     * @param $fileB64
     * @param $fileName
     * @param $subject
     * @param $date2
     * @param $type
     * @param $author
     * @param $comment
     * @param $ref
     * @param $amount
     * @param $tagsLst
     * @return bool
     */
    public function archive($fileB64, $fileName, $subject, $date2, $type, $author, $comment, $ref, $amount, $tagsLst)
    {
        $this->client = $this->setClientSoap($this->URL_WSDL_ARCHIVE);

        // Construction du tableau des paramètres
        $fileParam = array('content' => $fileB64,
                            'fileName' => $fileName,
                            'subject' => $subject,
                            'date1' => date(DATE_RFC2822),
                            'date2' => $date2,
                            'type' => $type,
                            'author' => $author,
                            'comment' => $comment,
                            'ref' => $ref,
                            'amount' => $amount,
                            'generic1' => 'gen1',
                            'generic2' => 'gen2',
                            'tag' => $tagsLst, );

        $params = array(
                'file' => $fileParam,
            );

        $result = $this->client->call('archive', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders());

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;

            return false;
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return false;
            } else {
                return $result;
            }
        }
    }

    /**
     * Fonction permettant de récupérer une archive
     *
     * @param $iua
     * @return bool|string
     */
    public function getArchivedFile($iua)
    {
        // Paramètre du service de connexion
        $params = array(
                        'iua' => $iua,
                        );

        $this->client = $this->setClientSoap($this->URL_WSDL_ARCHIVE);

        $result = $this->client->call('getArchive', $params, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders());

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;

            // La demande n'est pas associée à l'utilisateur
            if (strstr($this->client->faultstring, 'is not associated with the user')) {
                return 'DEMAND_NOT_ALLOWED';
            } else {
                // La demande n'a pas été validée, il est impossible de récupérer les fichiers signés
                if (strstr($this->client->faultstring, 'has not been signed. Impossible to get signed files')) {
                    return 'DEMAND_NOT_CONFIRMED';
                } else {
                    // Une erreur inconnue est apparue
                    return false;
                }
            }
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return false;
            } else {
                // Tableau contenant :
                //      - fileName : le nom du fichier
                //      - file : le fichier signé
                return $result;
            }
        }
    }

    /***********************************************************
     * FIN WEB SERVICES D'ARCHIVAGE
     ***********************************************************/

    /**
     * Permet de mettre en place le client de la requête en fonction du WSDL.
     *
     * @param $urlWsdl
     * @return \nusoap_client
     */
    protected function setClientSoap($urlWsdl)
    {
        // Instanciation du client SOAP
        $this->client = new \nusoap_client($urlWsdl, false, false, false, false, false, 0, 1000);

        $this->client->soap_defencoding = 'UTF-8';
        $this->client->decode_utf8 = false;

        // Mise en place des options CURL
        // Option curl
        $this->client->setUseCurl(true);

        // Mise en place du SSl si on l'active
        if ($this->enabledSSL) {
            // Mise en place des données d'authentification SSL
            $certRequest = array(
                'cainfofile' => $this->caChainClientLocation,
                'sslcertfile' => $this->certClientLocation,
                'sslkeyfile' => $this->privateKeyClientLocation,
                'passphrase' => $this->privateKeyClientPassword,
            );

            $this->client->setCredentials('', '', 'certificate', $certRequest);
            $this->client->setCurlOption(CURLOPT_SSLVERSION, 3);
            // @TODO : cette option sera à mettre à true. On utilisera un fichier contenant l'AC Yousign en tant que trustore
            $this->client->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
        }

        // @TODO : voir comment on lève une exception
        $err = $this->client->getError();
        if ($err) {
            echo '<h2>Constructor error</h2><pre>'.$err.'</pre>';
            echo '<h2>Debug</h2><pre>'.htmlspecialchars($this->client->getDebug(), ENT_QUOTES).'</pre>';
            exit();
        }

        return $this->client;
    }

    /***********************************************************
     * METHODES PRIVEES
     ***********************************************************/

    /**
     * Permet de générer les headers nécessaire à l'authentification de l'utilisateur final.
     *
     * @param bool $withUser
     * @return string
     */
    private function createHeaders($withUser = true)
    {
        if ($withUser === true) {
            return  '<apikey>'.$this->apikey.'</apikey>'.
                  '<username>'.$this->_login.'</username>'.
                  '<password>'.$this->_password.'</password>';
        } else {
            return '<apikey>'.$this->apikey.'</apikey>';
        }
    }

    /**
     * Parse le fichier de configuration.
     *
     * @param $pathParametersFile
     * @return $this
     */
    private function parseParametersFile($pathParametersFile)
    {
        $this->parameters = parse_ini_file($pathParametersFile, true);

        if (isset($this->parameters['environment'])) {
            $this->setEnvironment($this->parameters['environment']);
        }

        if (isset($this->parameters['login'])) {
            $this->setLogin($this->parameters['login']);
        }

        if (isset($this->parameters['password'])) {
            $password = $this->parameters['password'];
            if (empty($this->parameters['isEncryptedPassword'])
            || $this->parameters['isEncryptedPassword'] === false
            || $this->parameters['isEncryptedPassword'] === 'false') {
                $password = $this->encryptPassword($password);
            }

            $this->setPassword($password);
        }

        if (isset($this->parameters['api_key'])) {
            $this->setApiKey($this->parameters['api_key']);
        }

        if (isset($this->parameters['ssl_enabled'])) {
            $this->setEnabledSSL($this->parameters['ssl_enabled']);

            $this->setCertClientLocation($this->parameters['cert_client_location']);
            $this->setCaChainClientLocation($this->parameters['ca_chain_client_location']);
            $this->setPrivateKeyClientLocation($this->parameters['private_key_client_location']);
            $this->setPrivateKeyClientPassword($this->parameters['private_key_client_password']);
        }

        return $this;
    }

    /**
     * Récupère les informations d'une cosignature.
     *
     * @param array $parameters
     * @param bool $auth_required
     * @return mixed
     */
    private function getCosignInfo(array $parameters, $auth_required = true)
    {
        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);
        $result = $this->client->call('getInfosFromCosignatureDemand', $parameters, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders($auth_required));

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;
            if (isset($parameters['token']) && strstr(strtolower($this->client->faultstring), 'invalid token')) {
                return 'INVALID_TOKEN';
            } elseif (isset($parameters['idDemand']) && strstr($this->client->faultstring, 'is not associated with the user')) {
                return 'DEMAND_NOT_ALLOWED';
            } else {
                return false;
            }
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return false;
            } else {
                return $result;
            }
        }
    }

    /**
     * Récupère un fichier de cosignature.
     *
     * @param array $parameters
     * @param bool $auth_required
     * @return mixed
     */
    private function getCosignedFile(array $parameters, $auth_required = true)
    {
        $this->client = $this->setClientSoap($this->URL_WSDL_COSIGN);
        $result = $this->client->call('getCosignedFilesFromDemand', $parameters, self::API_NAMESPACE, self::API_NAMESPACE, $this->createHeaders($auth_required));

        if ($this->client->fault) {
            $this->errors[] = $this->client->faultstring;

            // La demande n'est pas associée à l'utilisateur
            if (strstr($this->client->faultstring, 'is not associated with the user')) {
                return 'DEMAND_NOT_ALLOWED';
            } else {
                // La demande n'a pas été validée, il est impossible de récupérer les fichiers signés
                if (strstr($this->client->faultstring, 'has not been signed. Impossible to get signed files')) {
                    return 'DEMAND_NOT_CONFIRMED';
                } else {
                    // Une erreur inconnue est apparue
                    return false;
                }
            }
        } else {
            $err = $this->client->getError();
            if ($err) {
                $this->errors = $err;

                return false;
            } else {
                $res = isset($result['fileName']) ?
                    $result : $result[0];

                return $res;
            }
        }
    }

    /***********************************************************
     * FIN DES METHODES PRIVEES
     ***********************************************************/
}
