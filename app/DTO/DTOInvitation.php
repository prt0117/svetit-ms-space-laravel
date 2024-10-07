<?php

namespace App\DTO;

class DTOInvitation
{

	public function __construct(
		public ?int $id,
		public string $spaceId,
		public string $creatorId,
		public string $userId,
		public ?int $roleId,
		public int $createdAt
	)
	{}
}