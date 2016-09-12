<?php

namespace Dinhkhanh\MongoDBAclBundle\Tests\Functional\Security\Problematic\Domain;

use Dinhkhanh\MongoDBAclBundle\Security\Problematic\Domain\AclManager;
use Dinhkhanh\MongoDBAclBundle\Security\Problematic\Model\AclManagerInterface;
use Dinhkhanh\MongoDBAclBundle\Tests\App\AbstractFunctionalTest;

class AclManagerTest extends AbstractFunctionalTest
{
    public function testServiceExistence()
    {
        $sut = $this->container->get('dinhkhanh.acl_manager');

        $this->assertInstanceOf(AclManagerInterface::class, $sut);
        $this->assertInstanceOf(AclManager::class, $sut);
    }
}