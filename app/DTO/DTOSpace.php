<?php

namespace App\DTO;

class DTOSpace
{

	public function __construct(
		public ?string $id,
		public string $name,
		public string $key,
		public bool $requestsAllowed,
		public int $createdAt
	)
	{}
}