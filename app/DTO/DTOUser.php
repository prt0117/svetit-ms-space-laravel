<?php

namespace App\DTO;

class DTOUser {

	public function __construct(
		public string $spaceId,
		public string $userId,
		public bool $isOwner,
		public int $joinedAt,
		public int $roleId
	)
	{}
}