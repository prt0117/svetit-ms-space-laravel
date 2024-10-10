<?php

namespace App\Services;
// todo - need to use Model Classes
use App\DTO\DTOPagingResult;
// todo - need to do something with DTOPagingResult
use App\DTO\DTOSpace;
use App\DTO\DTOUser;
use App\Repositories\CommonRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\SpaceRepository;
use App\Repositories\UserRepository;
use App\Repositories\LinkRepository;
use Illuminate\Support\Facades\Config;

class SpaceService {
	public int $itemsLimitForList;
	public string $defaultSpace;

	public CommonRepository $commonRepository;
	public SpaceRepository $spaceRepository;
	public UserRepository $userRepository;
	public LinkRepository $linkRepository;
	public InvitationRepository $invitationRepository;

	private bool $canCreate;
	private int $spacesLimitForUser;

	public function __construct(
		CommonRepository $commonRepository,
		SpaceRepository $spaceRepository,
		UserRepository $userRepository,
		LinkRepository $linkRepository,
		InvitationRepository $invitationRepository
	)
	{
		// todo - need to initialize all other members of class, especially default configuration values like itemsLimitForList
		$this->commonRepository = $commonRepository;
		$this->spaceRepository = $spaceRepository;
		$this->userRepository = $userRepository;
		$this->linkRepository = $linkRepository;
		$this->invitationRepository = $invitationRepository;
	}

	// todo - везде, где DTOPagingResult - возвращать не DTO, что-то другое
	public function GetList(string $userId, int $start, int $limit): DTOPagingResult
	{
		if (empty($this->defaultSpace)) {
			$defSpace = $this->spaceRepository->SelectByKey($this->defaultSpace);
			if (!$this->userRepository->IsUserInside($defSpace->id, $userId)) {
				$this->userRepository->Create($defSpace->id, $userId, false, Config::get("constants.roles.user"));
			}
		}

		return $this->commonRepository->SelectByUserId($userId, $start, $limit);
	}

	public function GetAvailableList(string $userId, int $start, int $limit): DTOPagingResult
	{
		return $this->commonRepository->SelectAvailable($userId, $start, $limit);
	}

	public function GetAvailableListBySpaceName(
		string $spaceName,
		string $userId,
		int $start,
		int $limit
	): DTOPagingResult
	{
		return $this->commonRepository->SelectAvailableBySpaceName(
			$spaceName,
			$userId,
			$start,
			$limit
		);
	}

	public function GetInvitationsList(int $start, int $limit, int $userId): DTOPagingResult
	{
		return $this->commonRepository->SelectInvitations($userId, $start, $limit);
	}

	public function GetInvitationListBySpace(
		string $spaceId,
		int $start,
		int $limit,
		string $userId
	): DTOPagingResult
	{
		if (!$this->userRepository->IsAdmin($spaceId, $userId))
			abort(403);

		return $this->commonRepository->SelectInvitationsBySpace(
			$spaceId,
			$userId,
			$start,
			$limit
		);
	}

	public function GetLinkList(int $start, int $limit, string $userId): DTOPagingResult
	{
		return $this->commonRepository->SelectSpaceLinkList($userId, $start, $limit);
	}

	public function GetLinkListBySpace(
		string $spaceId,
		int $start,
		int $limit,
		string $userId
	): DTOPagingResult
	{
		if ($this->userRepository->IsUserInside($spaceId, $userId))
			abort(403);

		return $this->linkRepository->SelectBySpace($spaceId, $start, $limit);
	}

	public function GetUserList(
		string $userId,
		string $spaceId,
		int $start,
		int $limit
	): DTOPagingResult
	{
		$isUserInside = $this->userRepository->IsUserInside($spaceId, $userId);
		if (!$isUserInside)
			abort(404);

		return $this->userRepository->Get($spaceId, $start, $limit);
	}

	public function IsSpaceExistsByKey(string $key): bool
	{
		return $this->spaceRepository->IsExists($key);
	}

	public function IsCanCreate()
	{
		return $this->canCreate;
	}

	public function CountInvitationAvailable(string $currentUserId): int
	{
		return $this->commonRepository->GetAvailableInvitationsCount($currentUserId);
	}

	public function KeyCreateCheck(string $key, string $userId): bool
	{
		if ($key == $userId)
			return true;

		if ($this->IsKeyReserved($key))
			return false;

		return preg_match('#[a-z0-9][a-z0-9_]+#i', $key);
	}

	public function KeyWeakCheck(string $key): bool
	{
		if ($this->IsKeyReserved($key))
			return false;

		return preg_match('#[a-z0-9_-]+#i', $key);
	}

	public function IsUserTimeouted(string $userId)
	{
		return $this->commonRepository->IsReadyForCreationByTime($userId);
	}

	public function IsLimitReached(string $userId)
	{
		$spacesWithUser = $this->commonRepository->GetCountSpacesWithUser($userId);
		return $this->spacesLimitForUser <= $spacesWithUser;
	}

	public function Create(
		string $name,
		string $key,
		bool $requestsAllowed,
		string $userId
	)
	{
		// todo - transaction needed;
		$spaceId = $this->spaceRepository->Create($name, $key, $requestsAllowed);
		$this->userRepository->Create(
			$spaceId,
			$userId,
			/*isOwner*/true,
			Config::get("constants.roles.admin")
		);
	}

	public function Delete(string $id)
	{
		$this->userRepository->DeleteBySpace($id);
		$this->invitationRepository->DeleteBySpace($id);
		$this->linkRepository->DeleteBySpace($id);
		$this->spaceRepository->Delete($id);
	}

	public function IsSpaceOwner(string $id, string $userId): bool
	{
		return $this->userRepository->IsOwner($id, $userId);
	}

	public function Invite(
		string $creatorId,
		string $spaceId,
		string $userId,
		?int $roleId
	)
	{
		$this->commonRepository->CreateInvitation($spaceId, $userId, $roleId, $creatorId);
	}

	public function ChangeRoleInInvitation(int $id, int $roleId, string $userId)
	{
		$invitation = $this->invitationRepository->SelectById($id);
		if (!$this->userRepository->IsAdmin($invitation->spaceId, $userId))
			abort(403);

		$this->invitationRepository->UpdateRole($id, $roleId);
	}

	public function ApproveInvitation(int $id, string $headerUserId)
	{
		$invitation = $this->invitationRepository->SelectById($id);
		$validRoles = [1, 2, 3];
		if (!empty($invitation->roleId)) {
			if (!in_array($invitation->roleId, $validRoles))
				abort(400, "Wrong role");
		} else
			abort(400, "Wrong role");

		if ($invitation->creatorId == $invitation->userId) {
			if ($invitation->userId == $headerUserId)
				abort(403);
			if (!$this->userRepository->IsAdmin($invitation->spaceId, $headerUserId))
				abort(403);
		} elseif ($invitation->userId != $headerUserId)
			abort(403);

		$this->invitationRepository->DeleteById($id);

		$this->userRepository->Create(
			$invitation->spaceId,
			$invitation->userId,
			false,
			$invitation->roleId
		);
	}

	public function DeleteInvitation(int $id, string $headerUserId)
	{
		$invitation = $this->invitationRepository->SelectById($id);
		$isEnoughRights = false;

		if ($invitation->creatorId == $invitation->userId
			&& $invitation->userId == $headerUserId
		) {
			$isEnoughRights = true;
		} elseif ($this->userRepository->IsAdmin($invitation->spaceId, $headerUserId)) {
			$isEnoughRights = true;
		}

		if (!$isEnoughRights)
			abort(403);

		$this->invitationRepository->DeleteById($id);
	}

	public function CheckExpiredAtValidity(int $expiredAt): bool
	{
		return $expiredAt > now()->timestamp;
	}

	public function CreateInvitationLink(string $spaceId, string $creatorId, string $name, int $expiredAt)
	{
		if (!$this->userRepository->IsAdmin($spaceId, $creatorId))
			abort(403);

		$this->linkRepository->Insert($spaceId, $creatorId, $name, $expiredAt);
	}

	public function DeleteInvitationLink(string $id, string $userId)
	{
		$link = $this->linkRepository->SelectById($id);

		if (!$this->userRepository->IsAdmin($link->spaceId, $userId))
			abort(403);

		$this->linkRepository->DeleteById($id);
	}

	public function GetById(string $id, string $userId): Space
	{
		$space = $this->spaceRepository->SelectById($id);
		if ($space->requestsAllowed)
			return $space;

		if ($this->invitationRepository->IsUserInvited($space->id, $userId))
			return $space;

		if ($this->userRepository->IsUserInside($space->id, $userId))
			abort(404);

		return $space;
	}

	public function GetByKey(string $key, string $userId): Space
	{
		$space = $this->spaceRepository->SelectByKey($key);
		if ($space->requestsAllowed)
			return $space;

		if (!$this->userRepository->IsUserInside($space->id, $userId))
			abort(404);

		return $space;
	}

	public function GetByLink(string $link): Space
	{
		return $this->commonRepository->SelectByLink($link);
	}

	public function InviteByLink(string $creatorId, string $linkId): bool
	{
		$link = $this->linkRepository->SelectById($linkId);
		$now = now()->timestamp;
		if ($link->expiredAt <= $now)
			return false;
		$this->commonRepository->CreateInvitation(
			$link->spaceId,
			$creatorId,
			null,
			$creatorId
		);
		return true;
	}

	public function DeleteUser(string $spaceId, string $userId, string $headerUserId)
	{
		$this->userRepository->Delete($spaceId, $userId, $headerUserId);
	}

	public function UpdateUser(DTOUser $updUser, string $headerUserId): bool
	{
		if ($updUser->userId == $headerUserId)
			return false;

		$caller = $this->userRepository->GetByIds($updUser->spaceId, $headerUserId);

		if ($caller->roleId != Config::get("constants.roles.admin"))
			return false;

		if ($updUser->isOwner && !$caller->isOwner)
			return false;

		$user = $this->userRepository->GetByIds($updUser->spaceId, $updUser->userId);

		if ($user->isOwner)
			return false;

		if ($updUser->isOwner) {
			// todo - transaction needed
			$this->userRepository->SetIsOwner($updUser->spaceId, $caller->userId, false);
			$this->userRepository->SetIsOwner($updUser->spaceId, $updUser->userId, true);
			return true;
		}

		$user->roleId = $updUser->roleId;
		$this->userRepository->Update($user);
		return true;
	}

	public function IsKeyReserved(string $key): bool
	{
		$reserved = ["u", "auth", "settings", "main", "api"];
		return in_array($key, $reserved);
	}

}