<?php

namespace app\DTO;

class DTORole {

	public function __construct(
		public int $id,
		public ?string $spaceId,
		public string $name
	)
	{}
}