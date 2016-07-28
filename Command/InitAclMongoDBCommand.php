<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PWalkow\MongoDBAclBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Set the indexes required by the MongoDB ACL provider
 *
 * @author Richard Shank <develop@zestic.com>
 */
class InitAclMongoDBCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('init:acl:mongodb')
            ->setDescription('Set the indexes required by the MongoDB ACL provider')
        ;
    }

    /**
     * @see Command
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // todo: change services and parameters when the configuration has been finalized
        $container = $this->getContainer();
        $mongo = $container->get('doctrine_mongodb.odm.default_connection');
        $dbName = $container->getParameter('doctrine_mongodb.odm.security.acl.database');
        $db = $mongo->selectDatabase($dbName);

        $oidCollection = $db->selectCollection($container->getParameter('doctrine_mongodb.odm.security.acl.oid_collection'));
        $oidCollection->ensureIndex(array('randomKey' => 1), array());
        $oidCollection->ensureIndex(array('identifier' => 1, 'type' => 1));

        $entryCollection = $db->selectCollection($container->getParameter('doctrine_mongodb.odm.security.acl.entry_collection'));
        $entryCollection->ensureIndex(array('objectIdentity.$id' => 1));

        $output->writeln('ACL indexes have been initialized successfully.');
    }
}
