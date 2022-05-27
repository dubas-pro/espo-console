<?php

declare(strict_types=1);

namespace Dubas\Console\Espo;

use Espo\Core\Application;
use Espo\Core\Container;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Crypt;
use Espo\Core\Utils\PasswordHash;
use Espo\Entities\Attachment;
use Exception;
use RuntimeException;

class EspoManager implements EspoManagerInterface
{
    private string $workingDirectory;

    public function setWorkingDirectory(string $workingDirectory): self
    {
        $this->workingDirectory = $workingDirectory;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->getContainer()->get('config');
    }

    public function createConfigWriter(): ConfigWriter
    {
        return $this->getContainer()->get('injectableFactory')->create(ConfigWriter::class);
    }

    public function importEntities(array $data = []): void
    {
        foreach ($data as $entityName => $entities) {
            foreach ($entities as $entityData) {
                $entityId = $entityData['id'] ?? null;

                if ('Attachment' === $entityName) {
                    $this->deleteAttachment($entityId);
                }

                $entity = $this->getEntityManager()->getEntity($entityName, $entityId);
                if (!$entity) {
                    $entity = $this->getEntityManager()->getEntity($entityName);
                }

                foreach ($entityData as $field => $value) {
                    if (in_array($entityName, ['EmailAccount', 'InboundEmail'], true)) {
                        if ('password' === $field) {
                            $value = $this->createCrypt()->encrypt($value);
                        }

                        if ('smtpPassword' === $field) {
                            $value = $this->createCrypt()->encrypt($value);
                        }
                    } else {
                        if ('password' === $field) {
                            $value = $this->createPasswordHash()->hash($value);
                        }
                    }

                    $entity->set($field, $value);
                }

                try {
                    $this->getEntityManager()->saveEntity($entity, [
                        'silent' => true,
                    ]);
                } catch (Exception $e) {
                    throw new Exception('Error importEntities: ' . $e->getMessage() . ', ' . print_r($entityData, true));
                }
            }
        }
    }

    public function getContainer(): Container
    {
        return $this->getApplication()->getContainer();
    }

    protected function getApplication(): Application
    {
        $bootstrap = $this->workingDirectory . '/bootstrap.php';

        if (!file_exists($bootstrap)) {
            throw new RuntimeException("File 'bootstrap.php' does not exist.");
        }

        include_once $bootstrap;

        $className = Application::class;

        if (!class_exists($className)) {
            throw new RuntimeException("Class '$className' does not exist.");
        }

        /** @var Application $application */
        $application = new $className();
        $application->setupSystemUser();

        return $application;
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }

    private function deleteAttachment(?string $entityId = null): void
    {
        $attachment = $this->getEntityManager()->getEntity(Attachment::ENTITY_TYPE, $entityId);
        if (!$attachment) {
            return;
        }

        $delete = $this->getEntityManager()
            ->getQueryBuilder()
            ->delete()
            ->from(Attachment::ENTITY_TYPE)
            ->where([
                'id' => $entityId,
            ])
            ->build();

        $this->getEntityManager()->removeEntity($attachment);
        $this->getEntityManager()->getQueryExecutor()->execute($delete);
    }

    private function createPasswordHash(): PasswordHash
    {
        return $this->getContainer()->get('injectableFactory')->create(PasswordHash::class);
    }

    private function createCrypt(): Crypt
    {
        return $this->getContainer()->get('injectableFactory')->create(Crypt::class);
    }
}
