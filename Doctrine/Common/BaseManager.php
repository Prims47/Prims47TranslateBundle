<?php
/*
 * This file is part of Prims47.
 *
 * (c) Ilan B <ilan.prims@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prims47\Bundle\TranslateBundle\Doctrine\Common;

use Doctrine\Common\Persistence\ObjectManager;

use Prims47\Bundle\TranslateBundle\Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class BaseManager
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $class;

    /**
     * Constructor
     *
     * @param ObjectManager $manager Model manager
     * @param string        $class   Name of model class
     */
    public function __construct(ObjectManager $manager, $class)
    {
        $this->manager = $manager;
        $this->class   = $class;
    }

    /**
     * Returns a new non-managed model.
     *
     * @return mixed
     */
    public function create()
    {
        return new $this->class;
    }

    /**
     * Saves an model.
     *
     * @param mixed $model Model to save
     * @param bool  $sync  Synchronize directly with database
     *
     * @throws \RuntimeException
     */
    public function save($model, $sync = false)
    {
        if (!$model instanceof $this->class) {
            throw new \RuntimeException(sprintf('Manager "%s" is unable to save model "%s"', get_class($this), get_class($model)));
        }

        $this->manager->persist($model);

        if ($sync) {
            $this->manager->flush();
        }
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     */
    public function flush()
    {
        $this->manager->flush();
    }

    /**
     * Deletes a model.
     *
     * @param mixed $model Model to save
     * @param bool  $sync  Synchronize directly with database
     *
     * @throws \RuntimeException
     */
    public function delete($model, $sync = false)
    {
        if (!$model instanceof $this->class) {
            throw new \RuntimeException(sprintf('Manager "%s" is unable to delete model "%s"', get_class($this), get_class($model)));
        }

        $this->manager->remove($model);

        if ($sync) {
            $this->manager->flush();
        }
    }

    /**
     * Clears the managers of ModelManager. All models that are currently managed in this manager become detached.
     *
     * @param string $modelName if given, only entities of this type will get detached
     */
    public function clear($modelName = null)
    {
        $this->manager->clear($modelName);
    }

    /**
     * Returns a "fresh" model by identifier.
     *
     * @param integer $id Model identifier
     *
     * @return object
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Returns entities according to criteria.
     *
     * @param array        $criteria An array of key/value matching AND conditions with field/value
     * @param null|array   $order    An array of key/value matching field/order (optional)
     * @param null|integer $limit    Maximum number of entities to return (optional)
     * @param null|integer $offset   Starting index to start from (optional)
     *
     * @return array An array of models
     */
    public function findBy(array $criteria, array $order = null, $limit = null, $offset = null)
    {
        return $this->getRepository()->findBy($criteria, $order, $limit, $offset);
    }

    /**
     * Finds objects by a set of criteria.
     *
     * @param array $criteria
     *
     * @return object The object.
     */
    public function findOneBy(array $criteria)
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    /**
     * Returns all models.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Sets the repository request default locale
     *
     * @param ContainerInterface $container
     *
     * @throws \InvalidArgumentException if repository is not an instance of TranslatableRepository
     */
    public function setRepositoryLocale(ContainerInterface $container)
    {
        if (!$this->getRepository() instanceof EntityRepository) {
            throw new \InvalidArgumentException('A BaseManager needs to be linked with a EntityRepository to sets default locale.');
        }

        /** @var Session $session */
        $session = $container->get('session');

        if ($session->has('locale')) {
            $this->getRepository()->setLocale($session->get('locale'));
        } else {
            $this->getRepository()->setLocale($container->get('request')->getLocale());
        }
    }

    /**
     * Gets the repository.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->manager->getRepository($this->class);
    }
}
