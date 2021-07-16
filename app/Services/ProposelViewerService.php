<?php
namespace App\Services;

use App\Repositories\ProposalViewersRepository;
use App\Models\ProposalViewer;

class ProposelViewerService {
	public function __construct(ProposalViewersRepository $repo)
	{
		$this->repo = $repo;
	}
	public function save(array $data)
	{
		return $this->repo->save($data);
	}
}  