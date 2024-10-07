<?php

namespace App\DTO;

class DTOGroup
{

	public function __construct(
		public ?int $id,
		public string $name,
		public string $description,
		public string $spaceId
	)
	{}

}