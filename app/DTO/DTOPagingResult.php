<?php

namespace App\DTO;

class DTOPagingResult
{

	public function __construct(
		public array $items,
		public int $total
	)
	{}
}