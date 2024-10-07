<?php

namespace App\DTO;

class DTOLink
{

	public function __construct(
		public ?string $id,
		public string $spaceId,
		public string $creatorId,
		public string $name,
		public int $createdAt,
		public int $expiredAt	)
	{}
}