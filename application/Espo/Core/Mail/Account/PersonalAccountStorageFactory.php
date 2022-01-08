<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Mail\Account;

use Espo\Core\Mail\Account\Storage\Params;
use Espo\Core\Mail\Mail\Storage\Imap;
use Espo\Core\Utils\Crypt;
use Espo\Core\Utils\Log;
use Espo\Core\InjectableFactory;

use Espo\Entities\UserData;
use Espo\Repositories\UserData as UserDataRepository;

use Espo\ORM\EntityManager;

use Throwable;

class PersonalAccountStorageFactory implements StorageFactory
{
    private Crypt $crypt;

    private Log $log;

    private InjectableFactory $injectableFactory;

    private EntityManager $entityManager;

    private string $storageClassName = Imap::class;

    public function __construct(
        Crypt $crypt,
        Log $log,
        InjectableFactory $injectableFactory,
        EntityManager $entityManager
    ) {
        $this->crypt = $crypt;
        $this->log = $log;
        $this->injectableFactory = $injectableFactory;
        $this->entityManager = $entityManager;
    }

    public function create(Account $account): Imap
    {
        $params = Params::createBuilder()
            ->setHost($account->getHost())
            ->setPort($account->getPort())
            ->setSecurity($account->getSecurity())
            ->setUsername($account->getUsername())
            ->setPassword(
                $this->crypt->decrypt($account->getPassword())
            )
            ->setEmailAddress($account->getEmailAddress())
            ->setUserId($account->getUser()->getId())
            ->setId($account->getId())
            ->setImapHandlerClassName($account->getImapHandlerClassName())
            ->build();

        return $this->createWithParams($params);
    }

    public function createWithParams(Params $params): Imap
    {
        $rawParams = [
            'host' => $params->getHost(),
            'port' => $params->getPort(),
            'username' => $params->getUsername(),
            'password' => $params->getPassword(),
            'emailAddress' => $params->getEmailAddress(),
            'userId' => $params->getUserId(),
            'imapHandler' => $params->getImapHandlerClassName(),
            'id' => $params->getId(),
        ];

        if ($params->getSecurity()) {
            $rawParams['security'] = $params->getSecurity();
        }

        $emailAddress = $rawParams['emailAddress'] ?? null;
        $userId = $rawParams['userId'] ?? null;
        $handlerClassName = $rawParams['imapHandler'] ?? null;

        $handler = null;
        $imapParams = null;

        if ($handlerClassName && !empty($rawParams['id'])) {
            try {
                $handler = $this->injectableFactory->create($handlerClassName);
            }
            catch (Throwable $e) {
                $this->log->error(
                    "EmailAccount: Could not create Imap Handler. Error: " . $e->getMessage()
                );
            }

            if (method_exists($handler, 'prepareProtocol')) {
                // for backward compatibility
                $rawParams['ssl'] = $rawParams['security'];

                $imapParams = $handler->prepareProtocol($rawParams['id'], $rawParams);
            }
        }

        if ($emailAddress && $userId && !$handlerClassName) {
            $emailAddress = strtolower($emailAddress);

            $userData = $this->getUserDataRepository()->getByUserId($userId);

            if ($userData) {
                $imapHandlers = $userData->get('imapHandlers') ?? (object) [];

                if (isset($imapHandlers->$emailAddress)) {
                    $handlerClassName = $imapHandlers->$emailAddress;

                    try {
                        $handler = $this->injectableFactory->create($handlerClassName);
                    }
                    catch (Throwable $e) {
                        $this->log->error(
                            "EmailAccount: Could not create Imap Handler for {$emailAddress}. Error: " .
                            $e->getMessage()
                        );
                    }

                    if (method_exists($handler, 'prepareProtocol')) {
                        $imapParams = $handler->prepareProtocol($userId, $emailAddress, $rawParams);
                    }
                }
            }
        }

        if (!$imapParams) {
            $imapParams = [
                'host' => $rawParams['host'],
                'port' => $rawParams['port'],
                'user' => $rawParams['username'],
                'password' => $rawParams['password'],
            ];

            if (!empty($rawParams['security'])) {
                $imapParams['ssl'] = $rawParams['security'];
            }
        }

        return new $this->storageClassName($imapParams);
    }

    private function getUserDataRepository(): UserDataRepository
    {
        /** @var UserDataRepository */
        return $this->entityManager->getRepository(UserData::ENTITY_TYPE);
    }
}