<?php

namespace Dinhkhanh\MongoDBAclBundle\Tests\Functional\Security\Domain;

use Dinhkhanh\MongoDBAclBundle\Security\Domain\MutableAclProvider;
use Dinhkhanh\MongoDBAclBundle\Security\Domain\AclProvider;
use Dinhkhanh\MongoDBAclBundle\Tests\App\AbstractFunctionalTest;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

/**
 * @group Integration
 */
class AclProviderTest extends AbstractFunctionalTest
{
    public function testServiceExistence()
    {
        $sut = $this->container->get('security.acl.provider');

        $this->assertInstanceOf(AclProviderInterface::class, $sut);
        $this->assertInstanceOf(MutableAclProviderInterface::class, $sut);
        $this->assertInstanceOf(AclProvider::class, $sut);
        $this->assertInstanceOf(MutableAclProvider::class, $sut);
    }
}