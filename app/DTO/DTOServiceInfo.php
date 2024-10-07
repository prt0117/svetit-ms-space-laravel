<?php

namespace App\DTO;

class DTOServiceInfo {

	public function __construct(
		public bool $canCreate,
		public int $invitationSize
	)
	{}
}