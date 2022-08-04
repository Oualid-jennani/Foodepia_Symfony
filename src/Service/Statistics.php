<?php


namespace App\Service;


use App\Repository\UserRepository;

class Statistics
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Statistics constructor.
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getCustomersCount()
    {
        return count( $this->userRepository->findAllCustomers());
    }

    public function getDriversCount()
    {
        return count($this->userRepository->findAllDrivers());
    }

    public function getChefsCount()
    {
        return count($this->userRepository->findAllChefs());
    }

    public function getCartersCount()
    {
        return count($this->userRepository->findAllCarters());
    }

}