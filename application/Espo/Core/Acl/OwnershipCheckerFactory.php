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

namespace Espo\Core\Acl;

use Espo\Core\{
    Utils\Metadata,
    InjectableFactory,
    Acl\Exceptions\NotImplemented,
    AclManager,
    Binding\BindingContainer,
    Binding\Binder,
    Binding\BindingData,
};

class OwnershipCheckerFactory
{
    private $defaultClassName = DefaultOwnershipChecker::class;

    private $metadata;

    private $injectableFactory;

    public function __construct(
        Metadata $metadata,
        InjectableFactory $injectableFactory
    ) {
        $this->metadata = $metadata;
        $this->injectableFactory = $injectableFactory;
    }

    /**
     * Create an ownership checker.
     *
     * @throws NotImplemented
     */
    public function create(string $scope, AclManager $aclManager): OwnershipChecker
    {
        $className = $this->getClassName($scope);

        $bindingContainer = $this->createBindingContainer($aclManager);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    private function getClassName(string $scope): string
    {
        $className = $this->metadata->get(['aclDefs', $scope, 'ownershipCheckerClassName']);

        if ($className) {
            return $className;
        }

        if (!$this->metadata->get(['scopes', $scope])) {
            throw new NotImplemented();
        }

        return $this->defaultClassName;
    }

    private function createBindingContainer(AclManager $aclManager): BindingContainer
    {
        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder->bindInstance(AclManager::class, $aclManager);

        return new BindingContainer($bindingData);
    }
}