<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface DomainRepositoryInterface
{
    /**
     * Insert or update a record. Returns the model instance.
     *
     * @param array $data Mapped data from the report mapper.
     * @return Model
     */
    public function upsert(array $data): Model;
}
