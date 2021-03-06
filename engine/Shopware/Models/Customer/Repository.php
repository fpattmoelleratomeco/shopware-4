<?php
/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Models
 * @subpackage Customer
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Oliver Denter
 * @author     $Author$
 */

namespace   Shopware\Models\Customer;
use         Shopware\Components\Model\ModelRepository;
/**
 * Repository for the customer model (Shopware\Models\Customer\Customer).
 *
 * The customer model repository is responsible to load all customer data.
 * It supports the standard functions like findAll or findBy and extends the standard repository for
 * some specific functions to return the model data as array.
 */
class Repository extends ModelRepository
{

    /**
     * Returns an instance of the \Doctrine\ORM\Query object which contains
     * all required fields for the backend customer list.
     * The filtering is performed on all columns.
     * The passed limit parameters for the list paging are placed directly into the query object.
     * To determine the total number of records, use the following syntax:
     * Shopware()->Models()->getQueryCount($query)
     *
     * @param  null $filter
     * @param null  $orderBy
     * @param null  $customerGroup
     * @param  null $limit
     * @param  null $offset
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery($filter = null, $customerGroup = null, $orderBy = null, $limit = null, $offset = null)
    {
        $builder = $this->getListQueryBuilder($filter, $customerGroup, $orderBy);
        if ($limit !== null) {
            $builder->setFirstResult($offset)
                    ->setMaxResults($limit);
        }
        return $builder->getQuery();
    }

    /**
     * Helper function to create the query builder for the "getListQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     * @param null $filter
     * @param null $customerGroup
     * @param null $orderBy
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getListQueryBuilder($filter = null, $customerGroup = null, $orderBy = null)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        //add the displayed columns
        $builder->select(array(
                'customer.id',
                'billing.number as number',
                'billing.firstName as firstName',
                'customer.firstLogin as firstLogin',
                'billing.lastName as lastName',
                'customergroups.name as customerGroup',
                'billing.company as company',
                'billing.zipCode as zipCode',
                'billing.city as city',
                $builder->expr()->count('orders.id') . ' as orderCount',
                'SUM(orders.invoiceAmount) as amount'
        ));

        $builder->from($this->getEntityName(), 'customer')
                ->join('customer.billing', 'billing')
                ->leftJoin('customer.group', 'customergroups')
                ->leftJoin('customer.orders', 'orders', \Doctrine\ORM\Query\Expr\Join::WITH, 'orders.status != -1 AND orders.status != 4')
                ->groupBy('customer.id');

        //filter the displayed columns with the passed filter string
        if (!empty($filter)) {
            $builder->where('billing.number LIKE ?1')         //Search only the beginning of the customer number.
                    ->orWhere('billing.firstName LIKE ?2')    //Full text search for the first name of the customer
                    ->orWhere('billing.lastName LIKE ?2')     //Full text search for the last name of the customer
                    ->orWhere('customer.firstLogin LIKE ?3')  //Search only for the end of the first login date.
                    ->orWhere('customergroups.name LIKE ?2')   //Full text search for the customer group
                    ->orWhere('billing.company LIKE ?2')      //Full text search for the company of the customer
                    ->orWhere('billing.city LIKE ?2')         //Full text search for the city of the customer
                    ->orWhere('billing.zipCode LIKE ?1')     //Search only the beginning of the customer number.
                    ->setParameter(1,       $filter . '%')
                    ->setParameter(2, '%' . $filter . '%')
                    ->setParameter(3, '%' . $filter      );
        }
        //filter the customers with the passed customer group parameter
        if (!empty($customerGroup)) {
            $builder->andWhere('customergroups.id = ?4')
                    ->setParameter(4, $customerGroup);
        }

        $this->addOrderBy($builder, $orderBy);
        return $builder;
    }

    /**
     * Calculates the total count of the getListQuery because getQueryCount and the paginator are to slow with huge data
     *
     * @param null $filter
     * @param null $customerGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getBackendListCountedBuilder($filter = null, $customerGroup = null)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        //add the displayed columns
        $builder->select(array(
            $builder->expr()->count('customer') . ' as customerCount',
        ));

        $builder->from($this->getEntityName(), 'customer')
                ->join('customer.billing', 'billing')
                ->leftJoin('customer.group', 'customergroups');


        //filter the displayed columns with the passed filter string
        if (!empty($filter)) {
            $builder->andWhere('billing.number LIKE ?1')         //Search only the beginning of the customer number.
                    ->orWhere('billing.firstName LIKE ?2')    //Full text search for the first name of the customer
                    ->orWhere('billing.lastName LIKE ?2')     //Full text search for the last name of the customer
                    ->orWhere('customer.firstLogin LIKE ?3')  //Search only for the end of the first login date.
                    ->orWhere('customergroups.name LIKE ?2')   //Full text search for the customer group
                    ->orWhere('billing.company LIKE ?2')      //Full text search for the company of the customer
                    ->orWhere('billing.city LIKE ?2')         //Full text search for the city of the customer
                    ->orWhere('billing.zipCode LIKE ?1')     //Search only the beginning of the customer number.
                    ->setParameter(1,       $filter . '%')
                    ->setParameter(2, '%' . $filter . '%')
                    ->setParameter(3, '%' . $filter      );
        }
        //filter the customers with the passed customer group parameter
        if (!empty($customerGroup)) {
            $builder->andWhere('customergroups.id = ?4')
                    ->setParameter(4, $customerGroup);
        }
        return $builder;
    }

    /**
     * Returns an instance of the \Doctrine\ORM\Query object which selects all data about a single customer.
     * @param $customerId
     * @internal param $id
     * @return \Doctrine\ORM\Query
     */
    public function getCustomerDetailQuery($customerId)
    {
        $builder = $this->getCustomerDetailQueryBuilder($customerId);

        return $builder->getQuery();
    }

    /**
     * Helper function to create the query builder for the "getCustomerDetailQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     * @param $customerId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCustomerDetailQueryBuilder($customerId)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select(array(
            'customer',
            'billing',
            'shipping',
            'debit',
            'attribute',
            'billingAttribute',
            'shippingAttribute',
            'locale.language',
            'shop.name as shopName',
            $builder->expr()->count('doneOrders.id') . ' as orderCount',
            'SUM(doneOrders.invoiceAmount) as amount',
            'SUM(canceledOrders.id as canceledOrderAmount'
        ));
        //join s_orders second time to display the count of canceled orders and the count and total amount of done orders
        $builder->from($this->getEntityName(), 'customer')
                ->join('customer.billing', 'billing')
                ->leftJoin('customer.shipping', 'shipping')
                ->leftJoin('customer.shop', 'shop')
                ->leftJoin('customer.languageSubShop', 'subShop')
                ->leftJoin('subShop.locale', 'locale')
                ->leftJoin('customer.debit', 'debit')
                ->leftJoin('customer.orders', 'doneOrders', \Doctrine\ORM\Query\Expr\Join::WITH, 'doneOrders.status <> -1 AND doneOrders.status <> 4' )
                ->leftJoin('customer.orders', 'canceledOrders', \Doctrine\ORM\Query\Expr\Join::WITH, 'canceledOrders.cleared = 16')
                ->leftJoin('billing.attribute', 'billingAttribute')
                ->leftJoin('shipping.attribute', 'shippingAttribute')
                ->leftJoin('customer.attribute', 'attribute')
                ->where($builder->expr()->eq('customer.id', $customerId));

        $builder->groupBy('customer.id');
        return $builder;
    }

    /**
     * Returns an instance of \Doctrine\ORM\Query object which selects a list of
     * all defined customer groups. Used to create the customer group price tabs on the article detail page in the article backend module.
     * @return \Doctrine\ORM\Query
     */
    public function getCustomerGroupsQuery()
    {
        $builder = $this->getCustomerGroupsQueryBuilder();
        return $builder->getQuery();
    }
    /**
     * Helper function to create the query builder for the "getCustomerGroupsQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCustomerGroupsQueryBuilder()
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        return $builder->select(array('groups'))
                       ->from('Shopware\Models\Customer\Group', 'groups')
                       ->orderBy('groups.id');
    }

    /**
     * Returns a list of orders for the passed customer id and filtered by the filter parameter.
     * @param      $customerId
     * @param null $filter
     * @param null $orderBy
     * @param null $limit
     * @param null $offset
     * @return \Doctrine\ORM\Query
     */
    public function getOrdersQuery($customerId, $filter = null, $orderBy = null, $limit = null, $offset = null)
    {
        $builder = $this->getOrdersQueryBuilder($customerId, $filter, $orderBy);
        if ($limit !== null) {
            $builder->setFirstResult($offset)
                    ->setMaxResults($limit);
        }
        return $builder->getQuery();
    }

    /**
     * Helper function to create the query builder for the "getOrdersQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     * @param      $customerId
     * @param      $filter
     * @param null $orderBy
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOrdersQueryBuilder($customerId, $filter = null, $orderBy = null)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        //select the different entities
        $builder->select(array(
            'orders.id as id',
            'orders.number as orderNumber',
            'orders.invoiceAmount as invoiceAmount',
            'orders.orderTime as orderTime',
            'dispatch.id as dispatchId',
            'orders.paymentId as paymentId',
            'orders.status as orderStatusId',
            'orders.cleared as paymentStatusId',
        ));

        //join the required tables for the order list
        $builder->from('Shopware\Models\Order\Order', 'orders')
                ->leftJoin('orders.payment', 'payment')
                ->leftJoin('orders.dispatch', 'dispatch')
                ->leftJoin('orders.orderStatus', 'orderStatus')
                ->leftJoin('orders.paymentStatus', 'paymentStatus');

        $expr = Shopware()->Models()->getExpressionBuilder();
        //filter the displayed columns with the passed filter string
        if (!empty($filter)) {
            $builder->where(
                $expr->andX(
                    $expr->eq('orders.customerId', $customerId),
                    $expr->orX(
                        $expr->like('orders.number', '?1'),        //Search only the beginning of the order number.
                        $expr->like('orders.invoiceAmount', '?3'),      //Search only the beginning of the order amount, replace , and . with _ wildcard
                        $expr->like('orders.orderTime', '?2'),          //Search only for the end of the order date.
                        $expr->like('payment.description', '?1'),       //Search only the beginning of the payment description.
                        $expr->like('dispatch.name', '?1'),             //Search only the beginning of the dispatch name.
                        $expr->like('orderStatus.description', '?1'),    //Search only the beginning of the order state.
                        $expr->like('paymentStatus.description', '?1')
                    )
                )
            )
            ->setParameter(1,       $filter . '%')
            ->setParameter(2, '%' . $filter      )
            ->setParameter(3, str_replace(".", "_",str_replace(",", "_", $filter)) . '%');
        } else {
            $builder->where($expr->eq('orders.customerId', $customerId));
        }
        $builder->andWhere($builder->expr()->notIn('orders.status', array('-1', '4')));

        $this->addOrderBy($builder, $orderBy);
        return $builder;
    }

    /**
     * Returns an instance of the \Doctrine\ORM\Query object which search for the attributes of the passed
     * shipping id.
     * @param $shippingId
     * @return \Doctrine\ORM\Query
     */
    public function getShippingAttributesQuery($shippingId)
    {
        $builder = $this->getShippingAttributesQueryBuilder($shippingId);
        return $builder->getQuery();
    }

    /**
     * Helper function to create the query builder for the "getShippingAttributesQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     * @param $shippingId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getShippingAttributesQueryBuilder($shippingId)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select(array('attribute'))
                ->from('Shopware\Models\Attribute\CustomerShipping', 'attribute')
                ->where('attribute.customerShippingId = ?1')
                ->setParameter(1, $shippingId);
        return $builder;
    }

    /**
     * Returns an instance of the \Doctrine\ORM\Query object which search for the attributes of the passed
     * billing id.
     * @param $billingId
     * @return \Doctrine\ORM\Query
     */
    public function getBillingAttributesQuery($billingId)
    {
        $builder = $this->getBillingAttributesQueryBuilder($billingId);
        return $builder->getQuery();
    }

    /**
     * Helper function to create the query builder for the "getBillingAttributesQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     * @param $billingId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getBillingAttributesQueryBuilder($billingId)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select(array('attribute'))
                ->from('Shopware\Models\Attribute\CustomerBilling', 'attribute')
                ->where('attribute.customerBillingId = ?1')
                ->setParameter(1, $billingId);
        return $builder;
    }

    /**
     * Returns an instance of the \Doctrine\ORM\Query object which search for the attributes of the passed
     * customer id.
     * @param $customerId
     * @return \Doctrine\ORM\Query
     */
    public function getAttributesQuery($customerId)
    {
        $builder = $this->getAttributesQueryBuilder($customerId);
        return $builder->getQuery();
    }

    /**
     * Helper function to create the query builder for the "getAttributesQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     * @param $customerId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAttributesQueryBuilder($customerId)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select(array('attribute'))
                ->from('Shopware\Models\Attribute\Customer', 'attribute')
                ->where('attribute.customerId = ?1')
                ->setParameter(1, $customerId);
        return $builder;
    }

    /**
     * Returns an instance of the \Doctrine\ORM\Query object which search for customers
     * with the passed email address. The passed customer id is excluded.
     * @param null $email
     * @param null $customerId
     * @return \Doctrine\ORM\Query
     */
    public function getValidateEmailQuery($email = null, $customerId = null,$shopId=null)
    {
        $builder = $this->getValidateEmailQueryBuilder($email, $customerId,$shopId);
        return $builder->getQuery();
    }

    /**
     * Helper function to create the query builder for the "getValidateEmailQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     * @param null $email
     * @param null $customerId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getValidateEmailQueryBuilder($email = null, $customerId = null,$shopId = null)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select(array('customer'))
                ->from($this->getEntityName(), 'customer')
                ->where('customer.email = ?1')
                ->setParameter(1, $email);

        if (!empty($customerId)) {
            $builder->andWhere('customer.id != ?2')
                    ->setParameter(2, $customerId);
            $builder->andWhere('customer.accountMode = 0');
        }

        if(!empty($shopId)){
            $builder->andWhere('customer.shopId  = ?3')
               ->setParameter(3, $shopId);
        }
        return $builder;
    }



    /**
     * Returns an instance of \Doctrine\ORM\Query object which selects a list of
     * all defined customer groups. Used to show all unselected customer groups to restrict the category
     *
     * @param $usedIds
     * @param $offset
     * @param $limit
     * @return \Doctrine\ORM\Query
     */
    public function getCustomerGroupsWithoutIdsQuery($usedIds, $offset, $limit)
    {
        $builder = $this->getCustomerGroupsWithoutIdsQueryBuilder($usedIds, $offset, $limit);
        return $builder->getQuery();
    }

    /**
     * Helper function to create the query builder for the "getCustomerGroupsQuery" function.
     * This function can be hooked to modify the query builder of the query object.
     *
     * @param $usedIds
     * @param $offset
     * @param $limit
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCustomerGroupsWithoutIdsQueryBuilder($usedIds, $offset, $limit)
    {
        $builder = $this->getEntityManager()->createQueryBuilder();
        $builder->select(array('groups'))->from('Shopware\Models\Customer\Group', 'groups');
        if (!empty($usedIds)) {
            $builder->where($builder->expr()->notIn("groups.id", $usedIds));
        }
        if ($limit !== null) {
            $builder->setFirstResult($offset)
                    ->setMaxResults($limit);
        }

        return $builder;
    }

}
