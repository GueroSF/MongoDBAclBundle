<?php

namespace PWalkow\MongoDBAclBundle\Tests\Security\Acl;

use Doctrine\MongoDB\Connection;
use IamPersistent\MongoDBAclBundle\Security\Acl\AclProvider;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
class AclProviderBenchmarkTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Connection */
    protected $connection;
    /** @var  array */
    protected $options;

    const DATABASE_NAME = 'aclBenchmark';
    const NUMBER_OF_ROWS = 200;

    public function testFindAcls()
    {
        $this->generateTestData();

        // get some random test object identities from the database
        $oids = array();
        $max = $this->connection->selectCollection(self::DATABASE_NAME, $this->options['oid_collection'])->find()->count();

        for ($i = 0; $i < $max; $i++) {
            $randomKey = rand(0, $max);
            $oid = $this->connection->selectCollection(
                self::DATABASE_NAME,
                $this->options['oid_collection'])->findOne(array('randomKey' => $randomKey)
            );
            $oids[] = new ObjectIdentity($oid['identifier'], $oid['type']);
        }

        $provider = $this->getProvider();

        $start = microtime(true);
        $provider->findAcls($oids);
        $time = microtime(true) - $start;
        echo "Total Time: " . $time . "s\n";
    }

    protected function setUp()
    {
        // comment the following line, and run only this test, if you need to benchmark
//        $this->markTestSkipped('Benchmarking skipped');

        if (!class_exists('Doctrine\MongoDB\Connection')) {
            $this->markTestSkipped('Doctrine2 MongoDB is required for this test');
        }

        $this->connection = new Connection('mongodb://localhost:27017');
        $this->connection->connect();
        $this->assertTrue($this->connection->isConnected());
        $this->connection->selectDatabase(self::DATABASE_NAME);
        $this->options = $this->getOptions();
    }

    protected function tearDown()
    {
        if ($this->connection) {
            $this->connection->dropDatabase(self::DATABASE_NAME);
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * This generates a huge amount of test data to be used mainly for benchmarking
     * purposes, not so much for testing. That's why it's not called by default.
     */
    protected function generateTestData()
    {
        $this->connection->selectCollection(self::DATABASE_NAME, $this->options['oid_collection'])->drop();
        $this->connection->selectCollection(self::DATABASE_NAME, $this->options['entry_collection'])->drop();
        $this->connection->selectCollection(self::DATABASE_NAME, $this->options['oid_collection'])->ensureIndex(array('randomKey' => 1), array());
        $this->connection->selectCollection(self::DATABASE_NAME, $this->options['oid_collection'])->ensureIndex(array('identifier' => 1, 'type' => 1));
        $this->connection->selectCollection(self::DATABASE_NAME, $this->options['entry_collection'])->ensureIndex(array('objectIdentity.$id' => 1));

        for ($i = 0; $i < self::NUMBER_OF_ROWS; $i++) {
            $this->generateAclHierarchy();
        }
    }

    protected function generateAclHierarchy()
    {
        $root = $this->generateAcl($this->chooseObjectIdentity(), null, array());

        $this->generateAclLevel(rand(1, 2), $root, array($root['_id']));
    }

    protected function generateAclLevel($depth, $parent, $ancestors)
    {
        $level = count($ancestors);
        for ($i = 0, $t = rand(1, 10); $i < $t; $i++) {
            $acl = $this->generateAcl($this->chooseObjectIdentity(), $parent, $ancestors);

            if ($level < $depth) {
                $this->generateAclLevel($depth, $acl, array_merge($ancestors, array($acl['_id'])));
            }
        }
    }

    protected function chooseObjectIdentity()
    {
        return array(
            'identifier' => $this->getRandomString(rand(20, 50)),
            'type' => $this->getRandomString(rand(20, 100)),
        );
    }

    protected function generateAcl($objectIdentity, $parent, $ancestors)
    {
        static $aclRandomKeyValue = 0; // used to retrieve random objects

        $oidCollection = $this->connection->selectCollection(
            self::DATABASE_NAME,
            $this->options['oid_collection']
        );

        $acl = array_merge($objectIdentity,
                           array(
                                'entriesInheriting' => (boolean)rand(0, 1),
                                'randomKey' => $aclRandomKeyValue,
                           )
        );
        $aclRandomKeyValue++;
        if ($parent) {
            $acl['parent'] = $parent;
            $acl['ancestors'] = $ancestors;
        }

        $oidCollection->insert($acl);

        $this->generateAces($acl);

        return $acl;
    }

    protected function chooseSid()
    {
        if (rand(0, 1) == 0) {
            return array('role' => $this->getRandomString(rand(10, 20)));
        } else {
            return array(
                'username' => $this->getRandomString(rand(10, 20)),
                'class' => $this->getRandomString(rand(10, 20)),
            );
        }
    }

    protected function generateAces($acl)
    {
        $sids = array();
        $fieldOrder = array();

        $collection = $this->connection->selectCollection(
            self::DATABASE_NAME,
            $this->options['entry_collection']);
        for ($i = 0; $i <= 30; $i++) {
            $query = array();

            $fieldName = rand(0, 1) ? null : $this->getRandomString(rand(10, 20));

            if (rand(0, 5) != 0) {
                $query['objectIdentity'] = array(
                    '$ref' => $this->options['oid_collection'],
                    '$id' => $acl['_id'],
                );
            }

            do {
                $sid = $this->chooseSid();
                $sidId = implode('-', array_values($sid));
            }
            while (array_key_exists($sidId, $sids) && in_array($fieldName, $sids[$sidId], true));

            if (!isset($sids[$sidId])) {
                $sids[$sidId] = array();
            }

            $sids[$sidId][] = $fieldName;
            $query['securityIdentity'] = $sid;

            $fieldOrder[$fieldName] = array_key_exists($fieldName, $fieldOrder) ? $fieldOrder[$fieldName] + 1 : 0;

            $strategy = rand(0, 2);
            if ($strategy === 0) {
                $query['grantingStrategy'] = PermissionGrantingStrategy::ALL;
            }
            else if ($strategy === 1) {
                $query['grantingStrategy'] = PermissionGrantingStrategy::ANY;
            }
            else {
                $query['grantingStrategy'] = PermissionGrantingStrategy::EQUAL;
            }

            $query['fieldName'] = $fieldName;
            $query['aceOrder'] = $fieldOrder[$fieldName];
            $query['securityIdentity'] = $sid;
            $query['mask'] = $this->generateMask();
            $query['granting'] = (boolean)rand(0, 1);
            $query['auditSuccess'] = (boolean)rand(0, 1);
            $query['auditFailure'] = (boolean)rand(0, 1);

            $collection->insert($query);
        }
    }

    protected function generateMask()
    {
        $i = rand(1, 30);
        $mask = 0;

        while ($i <= 30) {
            $mask |= 1 << rand(0, 30);
            $i++;
        }

        return $mask;
    }

    protected function getRandomString($length, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $s = '';
        $cLength = strlen($chars);

        while (strlen($s) < $length) {
            $s .= $chars[mt_rand(0, $cLength - 1)];
        }

        return $s;
    }

    /**
     * @return array
     */
    protected function getOptions()
    {
        return array(
            'oid_collection' => 'aclObjectIdentities',
            'entry_collection' => 'aclEntries',
        );
    }

    /**
     * @return PermissionGrantingStrategy
     */
    protected function getStrategy()
    {
        return new PermissionGrantingStrategy();
    }

    /**
     * @return AclProvider
     */
    protected function getProvider()
    {
        return new AclProvider(
            $this->connection,
            self::DATABASE_NAME,
            $this->getStrategy(),
            $this->getOptions()
        );
    }
}