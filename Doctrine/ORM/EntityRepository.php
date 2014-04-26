<?php
/*
 * This file is part of Prims47.
 *
 * (c) Ilan B <ilan.prims@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Prims47\Bundle\TranslateBundle\Doctrine\ORM;

use Doctrine\ORM\EntityRepository as BaseEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Query;

use Gedmo\Translatable\TranslatableListener;

class EntityRepository extends BaseEntityRepository
{
    /**
     * @var string
     */
    protected $locale;

    /**
     * Set locale.
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Returns translated one (or null if not found) result for given locale
     *
     * @param QueryBuilder $qb            A Doctrine query builder instance
     * @param string       $locale        A locale name
     * @param string       $hydrationMode A Doctrine results hydration mode
     *
     * @return QueryBuilder
     */
    public function getOneOrNullResult(QueryBuilder $qb, $locale = null, $hydrationMode = null)
    {
        return $this->getTranslateQuery($qb, $locale)->getOneOrNullResult($hydrationMode);
    }

    /**
     * Returns translated results for given locale
     *
     * @param QueryBuilder $qb            A Doctrine query builder instance
     * @param string       $locale        A locale name
     * @param string       $hydrationMode A Doctrine results hydration mode
     *
     * @return QueryBuilder
     */
    public function getResult(QueryBuilder $qb, $locale = null, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        return $this->getTranslateQuery($qb, $locale)->getResult($hydrationMode);
    }

    /**
     * Returns translated single result for given locale
     *
     * @param QueryBuilder $qb            A Doctrine query builder instance
     * @param string       $locale        A locale name
     * @param string       $hydrationMode A Doctrine results hydration mode
     *
     * @return QueryBuilder
     */
    public function getSingleResult(QueryBuilder $qb, $locale = null, $hydrationMode = null)
    {
        return $this->getTranslateQuery($qb, $locale)->getSingleResult($hydrationMode);
    }

    /**
     * Returns translated scalar result for given locale
     *
     * @param QueryBuilder $qb     A Doctrine query builder instance
     * @param string       $locale A locale name
     *
     * @return QueryBuilder
     */
    public function getScalarResult(QueryBuilder $qb, $locale = null)
    {
        return $this->getTranslateQuery($qb, $locale)->getScalarResult();
    }

    /**
     * Returns translated single scalar result for given locale
     *
     * @param QueryBuilder $qb     A Doctrine query builder instance
     * @param string       $locale A locale name
     *
     * @return QueryBuilder
     */
    public function getSingleScalarResult(QueryBuilder $qb, $locale = null)
    {
        return $this->getTranslateQuery($qb, $locale)->getSingleScalarResult();
    }

    /**
     * Returns translated array results for given locale
     *
     * @param QueryBuilder $qb     A Doctrine query builder instance
     * @param string       $locale A locale name
     *
     * @return QueryBuilder
     */
    public function getArrayResult(QueryBuilder $qb, $locale = null)
    {
        return $this->getTranslateQuery($qb, $locale)->getArrayResult();
    }

    /**
     * Alter query with a limit and skip.
     *
     * @param QueryBuilder $qb      Query builder
     * @param array        $options Options query
     */
    protected function alterQueryByLimitAndSkip(QueryBuilder $qb, array $options)
    {
        if (isset($options['limit']) && $options['limit'] > 0) {
            $qb->setMaxResults($options['limit']);
        }

        if (isset($options['skip']) && $options['skip'] > 0) {
            $qb->setFirstResult($options['skip']);
        }
    }

    /**
     * Alter query with a basic order-by.
     *
     * @param QueryBuilder $qb      Query builder
     * @param array        $options Options query
     * @param string       $alias   Alias query builder
     */
    protected function alterQueryByOrderBy(QueryBuilder $qb, array $options, $alias)
    {
        foreach ($options['sorts'] as $sort => $order) {
            $qb->orderBy(sprintf('%s.%s', $alias, $sort), $order);
        }
    }

    /**
     * Returns translate Doctrine query instance
     *
     * @param QueryBuilder $qb     A Doctrine query builder instance
     * @param string       $locale A locale name
     *
     * @return Query
     */
    protected function getTranslateQuery(QueryBuilder $qb, $locale = null)
    {
        $locale = null === $locale ? $this->locale : $locale;

        $query = $qb->getQuery();

        $query->setHint(
            Query::HINT_CUSTOM_OUTPUT_WALKER,
            'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
        );

        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);

        return $query;
    }
}
