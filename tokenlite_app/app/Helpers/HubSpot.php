<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Transaction;
use \HubSpot\Client;
use \HubSpot\Factory;
use Illuminate\Support\Facades\Log;

class HubSpot {

    const contactFields = [
        'firstname',
        'lastname',
        'email',
        'mobilephone',
        'country',
        'wallet_address_for_receipt',
        'hs_all_assigned_business_unit_ids',
    ];

    const dealFields = [
        'dealname',
        'dealstage',
        'pipeline',
        'amount',
        'purchase_currency',
        'tokens_received',
        'payment_transaction_id',
        'proposed_amount',
        'closedate',
        'hs_all_assigned_business_unit_ids',
    ];

    private $settings;
    private $ignore_setting_validation;

    public function __construct($ignore_setting_validation = false)
    {
        $this->setupSettings();

        $this->ignore_setting_validation = $ignore_setting_validation;
        $this->hubSpotInstance = $this->getHubSpotInstance();
    }

    private function setupSettings()
    {
        $this->settings = [
            'api_key' => get_setting('hubspot_api_key', ''),
            'business_unit_id' => get_setting('hubspot_business_unit_id', ''),
            'pipline_id' => get_setting('hubspot_pipeline_id', ''),
            'deal_stage_start_id' => get_setting('hubspot_deal_stage_start_id', ''),
            'deal_stage_end_id' => get_setting('hubspot_deal_stage_end_id', ''),
        ];
    }

    private function validateSettings()
    {
        $valid = true;
        $required_params = [
            'api_key',
            'business_unit_id',
            'pipline_id',
            'deal_stage_start_id',
            'deal_stage_end_id',
        ];
        foreach ($required_params as $param) {
            $valid_param = false;
            if (!isset($this->settings[$param])) {
                return false;
            }
            $value = $this->settings[$param];
            switch ($param) {
                case 'business_unit_id':
                    // This can potentially be '0', which would noramlly fail a !empty check
                    $valid_param = $value !== '';
                    break;
                default:
                    $valid_param = !empty($value);
                    break;
            }
            $valid = $valid && $valid_param;
        }
        return $valid;
    }

    private function getHubSpotInstance()
    {
        if (!$this->ignore_setting_validation && !$this->validateSettings()) {
            return false;
        }
        return Factory::createWithApiKey($this->settings['api_key']);
    }

    private function formatContactData(User $user)
    {
        // convert our name field to a first and last name
        $nameTokens = explode(' ', $user->name);
        $firstName = empty($nameTokens) ? '' : $nameTokens[0];
        array_shift($nameTokens);
        $lastName = empty($nameTokens) || count($nameTokens) < 1 ? '' : implode(' ', $nameTokens);

        return [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $user->email,
            'mobilephone' => $user->mobile,
            'country' => $user->nationality,
            'wallet_address_for_receipt' => $user->walletAddress,
            'hs_all_assigned_business_unit_ids' => $this->settings['business_unit_id']
        ];
    }

    private function formatDealData(User $user, Transaction $transaction)
    {
        $data = [
            'dealname' => $user->name,
            'pipeline' => $this->settings['pipline_id'],
            'purchase_currency' => strtoupper($transaction->currency),
            'proposed_amount' => $transaction->base_amount,
            'hs_all_assigned_business_unit_ids' => $this->settings['business_unit_id'],
            'payment_transaction_id' => $transaction->payment_id
        ];

        if ($transaction->receive_amount > 0) {
            $data['amount'] = round(
                $transaction->tokens * $transaction->base_currency_rate,
                min_decimal()
            );
        }

        if ($transaction->status == 'approved') {
            $data['tokens_received'] = round($transaction->total_tokens, min_decimal());
            $data['closedate'] = (new Carbon($transaction->updated_at))->toISOString();
            $data['dealstage'] = $this->settings['deal_stage_end_id'];
        } else {
            $data['dealstage'] = $this->settings['deal_stage_start_id'];
        }

        return $data;
    }

    private function createDeal(User $user, Transaction $transaction)
    {
        try {
            $dealInput = new Client\Crm\Deals\Model\SimplePublicObjectInput([
                'properties' => $this->formatDealData($user, $transaction)
            ]);
            $deal = $this->hubSpotInstance->crm()->deals()->basicApi()->create($dealInput);
            return $deal;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

    private function updateDeal(int $dealId, User $user, Transaction $transaction)
    {
        try {
            $dealInput = new Client\Crm\Deals\Model\SimplePublicObjectInput();
            $dealInput->setProperties($this->formatDealData($user, $transaction));

            $this->hubSpotInstance->crm()->deals()->basicApi()->update($dealId, $dealInput);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

    private function createContact(User $user)
    {
        try {
            $contactInput = new Client\Crm\Contacts\Model\SimplePublicObjectInput();
            $contactInput->setProperties($this->formatContactData($user));

            $this->hubSpotInstance->crm()->contacts()->basicApi()->create($contactInput);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

    private function updateContact(int $contactId, User $user)
    {
        try {
            $contactInput = new Client\Crm\Contacts\Model\SimplePublicObjectInput();
            $contactInput->setProperties($this->formatContactData($user));

            $this->hubSpotInstance->crm()->contacts()->basicApi()->update($contactId, $contactInput);
            return true;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

    private function getDealByPaymentId(int $paymentId)
    {
        try {
            // this code is for searching contacts by email
            $filter = new \HubSpot\Client\Crm\Deals\Model\Filter();
            $filter
                ->setOperator('EQ')
                ->setPropertyName('payment_transaction_id')
                ->setValue($paymentId);

            $filterGroup = new Client\Crm\Deals\Model\FilterGroup();
            $filterGroup->setFilters([$filter]);

            $searchRequest = new Client\Crm\Deals\Model\PublicObjectSearchRequest();
            $searchRequest->setFilterGroups([$filterGroup]);

            // this allows all the properties we need to be within the response
            $searchRequest->setProperties($this::dealFields);

            // gets the search results from hubspot
            $deals = $this->hubSpotInstance->crm()->deals()->searchApi()->doSearch($searchRequest)->getResults();
            // that payment transaction id should be unique to each deal, so we take the first one
            if (!empty($deals)) {
                return $deals[0];
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

    private function associateContactWithDeal(int $contactId, int $dealId)
    {
        try {
            $apiResponse = $this->hubSpotInstance->crm()->contacts()->associationsApi()->create(
                $contactId, 
                'deal',
                $dealId, 
                'contact_to_deal'
            );
            $associations = $apiResponse->getAssociations();
            foreach ($associations as $association) {
                $results = $association->getResults();
                foreach ($results as $result) {
                    // double check our deal was successfully associated
                    if ($result->getId() == $dealId) {
                        return true;
                    }
                }
            }
            return false;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

    private function getContactByUser(User $user)
    {
        try {
            // this code is for searching contacts by email
            $filter = new \HubSpot\Client\Crm\Contacts\Model\Filter();
            $filter
                ->setOperator('EQ')
                ->setPropertyName('email')
                ->setValue($user->email);

            $filterGroup = new Client\Crm\Contacts\Model\FilterGroup();
            $filterGroup->setFilters([$filter]);

            $searchRequest = new Client\Crm\Contacts\Model\PublicObjectSearchRequest();
            $searchRequest->setFilterGroups([$filterGroup]);

            // this allows all the properties we need to be within the response
            $searchRequest->setProperties($this::contactFields);

            // gets the search results from hubspot
            $searchResult = $this->hubSpotInstance->crm()->contacts()->searchApi()->doSearch($searchRequest)->getResults();

            if (!empty($searchResult)) {
                return $searchResult[0];
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return false;
    }

    private function putContact(User $user)
    {
        $contact = $this->getContactByUser($user);
        if (empty($contact)) {
            // create a new contact
            return $this->createContact($user);
        } else {
            $props = $contact->getProperties();
            // If the contact doesn't already have a business unit, then we don't wanna update it
            if (empty($props['hs_all_assigned_business_unit_ids'])) {
                return false;
            }
            $businessUnitId = $props['hs_all_assigned_business_unit_ids'];
            // only update contacts that belong to the business unit defined in settings
            if (
                $businessUnitId == $this->settings['business_unit_id'] ||
                $businessUnitId == ''
            ) {
                return $this->updateContact($contact->getId(), $user);
            }
        }
        return false;
    }

    private function putDeal(User $user, Transaction $transaction)
    {
        $contact = $this->getContactByUser($user);
        if (empty($contact)) {
            // theoretically this should never happen since the contact should already exist by now
            Log::error('Contact was not found when attempting to update/create a deal');
            return false;
        }
        $deal = $this->getDealByPaymentId($transaction->payment_id);
        if (empty($deal)) {
            $deal = $this->createDeal($user, $transaction);
            if (empty($deal)) {
                return false;
            }
            return $this->associateContactWithDeal($contact->getId(), $deal->getId());
        } else {
            return $this->updateDeal($deal->getId(), $user, $transaction);
        }
        return true;
    }

    /**
     * Will update/create (if it doesn't exist) contact and transaction details
     *
     * @param  int         $user_id        unique identifier for a user
     * @param  int         $transaction_id unique identifier for a transaction
     */
    public function put(User $user, Transaction $transaction = null)
    {
        // ensure api creds are setup and instance was successfully created
        if (empty($this->hubSpotInstance)) {
            return;
        }
        // make sure user is not null or undefined
        if (empty($user)) {
            return;
        }

        // update/create contact
        $success = $this->putContact($user);

        // make sure transaction is defined and is associated with this user
        if (
            !$success ||
            empty($transaction) || 
            $user->id != $transaction->user
        ) {
            return;
        }

        // update/create lead for this transaction
        $this->putDeal($user, $transaction);
    }
}
