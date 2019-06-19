<?php
/**
 * GiaPhuGroup Co., Ltd.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GiaPhuGroup.com license that is
 * available through the world-wide-web at this URL:
 * https://www.giaphugroup.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    PHPCuong
 * @package     PHPCuong_CustomCronJob
 * @copyright   Copyright (c) 2019-2020 GiaPhuGroup Co., Ltd. All rights reserved. (http://www.giaphugroup.com/)
 * @license     https://www.giaphugroup.com/LICENSE.txt
 */

namespace PHPCuong\CustomCronJob\Cron;

class NewsletterSubscription
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $subscriberFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerFactory = $customerFactory;
        $this->subscriberFactory = $subscriberFactory;
        $this->logger = $logger;
    }

    /**
     * Customer newsletter subscription
     *
     * @return void
     */
    public function execute()
    {
        // The firstly we will get all the customer has been subscribed to the newsletter.
        $customersSubscribed = $this->subscriberFactory->create()->getCollection()->addFieldToFilter(
            'customer_id', ['notnull' => true]
        );

        $customerIdsSubscribed = [];
        foreach ($customersSubscribed as $customerSubscribed) {
            $customerIdsSubscribed[] = $customerSubscribed->getCustomerId();
        }

        // Get all the existing customers
        $customers = $this->customerFactory->create()->getCollection();

        if (!empty($customerIdsSubscribed)) {
            // nin = not in the array
            $customers = $customers->addFieldToFilter('entity_id', ['nin' => $customerIdsSubscribed]);
            // We skip the customers subscribed to the newsletter
        }

        foreach ($customers as $customer) {
            try {
                $this->subscriberFactory->create()->setData(
                    [
                        'store_id' => $customer->getStoreId(),
                        'customer_id' => $customer->getId(),
                        'subscriber_email' => $customer->getEmail(),
                        'subscriber_status' => \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED,
                        'subscriber_confirm_code' => $this->subscriberFactory->create()->randomSequence()
                    ]
                )->save();
            } catch (\Exception $e) {
                // Save the log text if got the issue while saving the customer newsletter subscription
                $this->logger->info($e);
            }
        }
    }
}
