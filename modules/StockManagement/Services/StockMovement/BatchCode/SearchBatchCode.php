<?php 

namespace Modules\StockManagement\Services\StockMovement\BatchCode;

use Modules\StockManagement\Repositories\BatchCode\BatchCodeRepository;

class SearchBatchCode
{
    protected BatchCodeRepository $batchCodeRepo;

    public function __construct(BatchCodeRepository $repo)
    {
        $this->batchCodeRepo = $repo;
    }

    public function handle(array $filters)
    {
        return $this->batchCodeRepo->search($filters);
    }
}
