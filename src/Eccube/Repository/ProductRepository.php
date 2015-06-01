<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ProductRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ProductRepository extends EntityRepository
{
    /**
     * @var array
     */
    private $config;

    /**
     * setConfig
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * get Product.
     *
     * @param  integer $productId
     * @return \Eccube\Entity\Product
     *
     * @throws NotFoundHttpException
     */
    public function get($productId)
    {
        // Product
        try {
            $qb = $this->createQueryBuilder('p')
                ->andWhere('p.id = :id');

            $product = $qb
                ->getQuery()
                ->setParameters(array(
                    'id' => $productId,
                ))
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw new NotFoundHttpException();
        }

        return $product;
    }

    /**
     * get query builder.
     *
     * @param  array $searchData
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchData($searchData)
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.Status = 1');

        // category
        $categoryJoin = false;
        if (!empty($searchData['category_id']) && $searchData['category_id']) {
            $Categories = $searchData['category_id']->getSelfAndDescendants();
            if ($Categories) {
                $qb
                    ->innerJoin('p.ProductCategories', 'pct')
                    ->innerJoin('pct.Category', 'c')
                    ->andWhere($qb->expr()->in('pct.Category', ':Categories'))
                    ->setParameter('Categories', $Categories);
                $categoryJoin = true;
            }
        }

        // name
        if (!empty($searchData['name']) && $searchData['name']) {
            $keywords = preg_split('/[\s　]+/u', $searchData['name'], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($keywords as $keyword) {
                $qb
                    ->andWhere('p.name LIKE :keyword')
                    ->setParameter('keyword', '%' . $keyword . '%');
            }
        }

        // Order By
        // 価格順
        if (!empty($searchData['orderby']) && $searchData['orderby'] === '1') {
            $qb->innerJoin('p.ProductClasses', 'pc');
            $qb->orderBy('pc.price02', 'ASC');
        // 新着順
        } elseif (!empty($searchData['orderby']) && $searchData['orderby'] === '2') {
            $qb->innerJoin('p.ProductClasses', 'pc');
            $qb->orderBy('pc.create_date', 'DESC');
        } else {
            if ($categoryJoin == false) {
                $qb
                    ->innerJoin('p.ProductCategories', 'pct')
                    ->innerJoin('pct.Category', 'c');
            }
            $qb
                ->orderBy('c.rank', 'DESC')
                ->addOrderBy('pct.rank', 'DESC')
                ->addOrderBy('p.id', 'DESC');
        }

        return $qb;
    }

    /**
     * get query builder.
     *
     * @param  array $searchData
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilderBySearchDataForAdmin($searchData)
    {
        $qb = $this->createQueryBuilder('p');

        // id
        if (!empty($searchData['id']) && $searchData['id']) {
            $qb
                ->andWhere('p.id = :id')
                ->setParameter('id', $searchData['id']);
        }

        // code
        if (!empty($searchData['code']) && $searchData['code']) {
            $qb
                ->innerJoin('p.ProductClasses', 'pc')
                ->andWhere('pc.code LIKE :code')
                ->setParameter('code', '%' . $searchData['code'] . '%');
        }

        // name
        if (!empty($searchData['name']) && $searchData['name']) {
            $keywords = preg_split('/[\s　]+/u', $searchData['name'], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($keywords as $keyword) {
                $qb
                    ->andWhere('p.name LIKE :name')
                    ->setParameter('name', '%' . $keyword . '%');
            }
        }

        // category
        if (!empty($searchData['category_id']) && $searchData['category_id']) {
            $Categories = $searchData['category_id']->getSelfAndDescendants();
            if ($Categories) {
                $qb
                    ->innerJoin('p.ProductCategories', 'pct')
                    ->innerJoin('pct.Category', 'c')
                    ->andWhere($qb->expr()->in('pct.Category', ':Categories'))
                    ->setParameter('Categories', $Categories);
            }
        }

        // status
        if (!empty($searchData['status']) && $searchData['status']->toArray()) {
            $qb
                ->andWhere($qb->expr()->in('p.Status', ':Status'))
                ->setParameter('Status', $searchData['status']->toArray());
        }

        // product_status
        if (!empty($searchData['product_status']) && $searchData['product_status']->toArray()) {
            $qb
                ->innerJoin('p.ProductStatuses', 'ps')
                ->andWhere($qb->expr()->in('ps.Status', ':ProductStatues'))
                ->setParameter('ProductStatues', $searchData['product_status']->toArray());
        }

        // maker_id
        if (!empty($searchData['maker_id']) && $searchData['maker_id']->toArray()) {
            $qb
                ->andWhere('p.Maker = :Maker')
                ->setParameter('Maker', $searchData['maker_id']->toArray());
        }

        // create date start
        if (!empty($searchData['register_start'])) {
            $qb
                ->andWhere('p.create_date >= :create_date_start')
                ->setParameter('create_date_start', $searchData['register_start']);
        }

        // create date end
        if (!empty($searchData['register_end'])) {
            // 日付のみ保持しているため, 時刻を設定.
            $endDate = clone $searchData['register_end'];
            $endDate->setTime(23, 59, 59);
            $qb
                ->andWhere('p.create_date <= :create_date_end')
                ->setParameter('create_date_end', $endDate);
        }

        // TODO 更新日の検索を実装

        // Order By
        $qb
            ->orderBy('p.update_date', 'DESC');

        return $qb;
    }

    /**
     * get query builder.
     *
     * @param $Customer
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getFavoriteProductQueryBuilderByCustomer($Customer)
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.CustomerFavoriteProducts', 'cfp')
            ->where('cfp.Customer = :Customer AND p.Status = 1')
            ->setParameter('Customer', $Customer);

        // Order By
        $qb->addOrderBy('cfp.create_date', 'DESC');

        return $qb;
    }
}
