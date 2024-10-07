<?php

namespace App\Services;
use App\DTO\DTOPagingResult;
use App\Repositories\CommonRepository;
use App\Repositories\InvitationRepository;
use App\Repositories\SpaceRepository;
use App\Repositories\UserRepository;
use App\Repositories\LinkRepository;
use App\Repositories\InvitationRepository;
use Illuminate\Support\Facades\Config;

class SpaceService {

	std::vector<model::SpaceUser> _users;

	int _itemsLimitForList;
	int _tokenExpireSecs;

	public string $defaultSpace;
	private bool $canCreate;
	private int $spacesLimitForUser;
	public CommonRepository $commonRepository;
	public SpaceRepository $spaceRepository;
	public UserRepository $userRepository;
	public LinkRepository $linkRepository;
	public InvitationRepository $invitationRepository;

	public function __construct(
		CommonRepository $commonRepository,
		SpaceRepository $spaceRepository,
		UserRepository $userRepository,
		LinkRepository $linkRepository,
		InvitationRepository $invitationRepository
	)
	{
		// todo - need to initialize all members of class
		$this->commonRepository = $commonRepository;
		$this->spaceRepository = $spaceRepository;
		$this->userRepository = $userRepository;
		$this->linkRepository = $linkRepository;
		$this->invitationRepository = $invitationRepository;
	}

	public function GetList(string $userId, int $start, int $limit): DTOPagingResult
	{
		if (empty($this->defaultSpace)) {
			$defSpace = $this->spaceRepository->SelectByKey($this->defaultSpace);
			if (!$this->userRepository->IsUserInside($defSpace->id, $userId)) {
				$this->userRepository->Create($defSpace->id, $userId, false, Config::get("constants.roles.user"));
			}
		}

		return $this->$commonRepository()->SelectByUserId($userId, $start, $limit);
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
		bool $isUserInside = $this->userRepository->IsUserInside($spaceId, $userId);
		if (!$userInside)
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
		$this->commonRepository->GetAvailableInvitationsCount($currentUserId);
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
		int $spacesWithUser = $this->commonRepository->GetCountSpacesWithUser($userId);
		return $this->spacesLimitForUser <= $spacesWithUser;
	}

	public function Create(
		string $name,
		string $key,
		bool $requestsAllowed,
		string $userId
	): void
	{
		$spaceId = $this->spaceRepository->Create($name, $key, $requestsAllowed);
		$this->userRepository->Create(
			$spaceId,
			$userId,
			/*isOwner*/true,
			Config::get("constants.roles.admin")
		);
	}

	public function Delete(string $id): void
	{
		$this->userRepository->DeleteBySpace($id);
		$this->invitationRepository->DeleteBySpace($id);
		$this->linkRepository->DeleteBySpace($id);
		$this->spaceRepository->Delete($id);
	}

	public function IsSpaceOwner(string $id, string $userid)
	{
		return $this->userRepository->IsOwner($id, $userId);
	}

	public function Invite(
		string $creatorId,
		string $spaceId,
		string $userId,
		?int $roleId
	): void
	{
		$this->commonRepository->CreateInvitation($spaceId, $userId, $roleId, $creatorId);
	}

	public function ChangeRoleInInvitation(int $id, int $roleId, string $userId)
	{
		$invitation = $this->spaceInvitation
	}


void Service::ChangeRoleInInvitation(int id, int roleId, const std::string& userId) {
	const auto invitation = _repo.SpaceInvitation().SelectById(id);
	if (!_repo.SpaceUser().IsAdmin(invitation.spaceId, userId))
		throw errors::Forbidden403();

	_repo.SpaceInvitation().UpdateRole(id, roleId);
}

void Service::ApproveInvitation(int id, const std::string& headerUserId) {
	model::SpaceInvitation invitation = _repo.SpaceInvitation().SelectById(id);

	// тут надо переписать на получение списка всех ролей для Пространства из таблицы space.role, также, видимо, надо в метод добавить параметр spaceId
	static const std::set<int> valid_roles{
		1, 2, 3
	};
	if (invitation.roleId.has_value()){
		if (!valid_roles.contains(invitation.roleId.value()))
			throw errors::BadRequest400("Wrong role");
	}
	else
		throw errors::BadRequest400("Wrong role");

	// Я прошусь/хочет к нам - creatorId == userId
	if (invitation.creatorId == invitation.userId) {
		if (invitation.userId == headerUserId)
			throw errors::Forbidden403();
		// Я прошусь/хочет к нам - может одобрить только админ
		if (!_repo.SpaceUser().IsAdmin(invitation.spaceId, headerUserId))
			throw errors::Forbidden403();
	}
	// Меня/мы пригласили - может одобрить только пользователь которого пригласили
	else if (invitation.userId != headerUserId)
		throw errors::Forbidden403();

	_repo.SpaceInvitation().DeleteById(id);

	_repo.SpaceUser().Create(invitation.spaceId, invitation.userId, false, invitation.roleId);
}

void Service::DeleteInvitation(int id, const std::string& headerUserId) {
	const auto invitation = _repo.SpaceInvitation().SelectById(id);

	bool isEnoughRights = false;

	if (invitation.creatorId == invitation.userId && invitation.userId == headerUserId) {
		// I want to join
		isEnoughRights = true;
	} else if (_repo.SpaceUser().IsAdmin(invitation.spaceId, headerUserId)) {
		// other cases need admin rights
		isEnoughRights = true;
	}

	if (!isEnoughRights)
		throw errors::Forbidden403();

	_repo.SpaceInvitation().DeleteById(id);
}

bool Service::CheckExpiredAtValidity(const std::chrono::system_clock::time_point& expiredAt) {
	// todo - is it right way to compare timestamps in this current situation?
	return expiredAt > std::chrono::system_clock::now();
}

void Service::CreateInvitationLink(const boost::uuids::uuid& spaceId, const std::string& creatorId, const std::string& name, const std::chrono::system_clock::time_point& expiredAt) {
	if (!_repo.SpaceUser().IsAdmin(spaceId, creatorId))
		throw errors::Forbidden403();

	_repo.SpaceLink().Insert(
		spaceId,
		creatorId,
		name,
		expiredAt
	);
}

void Service::DeleteInvitationLink(const boost::uuids::uuid& id, const std::string& userId) {
	const auto link = _repo.SpaceLink().SelectById(id);

	if (!_repo.SpaceUser().IsAdmin(link.spaceId, userId))
		throw errors::Forbidden403();

	_repo.SpaceLink().DeleteById(id);
}

model::Space Service::GetById(const boost::uuids::uuid& id, const std::string& userId) {
	const auto space = _repo.Space().SelectById(id);
	if (space.requestsAllowed)
		return space;

	if (_repo.SpaceInvitation().IsUserInvited(space.id, userId))
		return space;

	if (!_repo.SpaceUser().IsUserInside(space.id, userId))
		throw errors::NotFound404{};
	return space;
}

model::Space Service::GetByKey(const std::string& key, const std::string& userId) {
	const auto space = _repo.Space().SelectByKey(key);
	if (space.requestsAllowed)
		return space;

	if (!_repo.SpaceUser().IsUserInside(space.id, userId))
		throw errors::NotFound404{};
	return space;
}

model::Space Service::GetByLink(const boost::uuids::uuid& link) {
	return _repo.SelectByLink(link);
}


bool Service::InviteByLink(const std::string& creatorId, const boost::uuids::uuid& linkId) {
	const auto link = _repo.SpaceLink().SelectById(linkId);

	const auto now = std::chrono::system_clock::now();
	if (link.expiredAt <= now)
		return false;
	_repo.CreateInvitation(link.spaceId, creatorId, std::nullopt, creatorId);
	return true;
}

void Service::DeleteUser(const boost::uuids::uuid& spaceId, const std::string& userId, const std::string& headerUserId) {
	_repo.SpaceUser().Delete(spaceId, userId, headerUserId);
}

bool Service::UpdateUser(const model::SpaceUser& updUser, const std::string& headerUserId) {
	// Нет смысла менять что-то у самого себя:
	if (updUser.userId == headerUserId)
		return false;

	const auto caller = _repo.SpaceUser().GetByIds(updUser.spaceId, headerUserId);

	// Только админ может что-то менять
	if (caller.roleId != consts::kRoleAdmin)
		return false;

	// Только владелец может сменить владельца
	if (updUser.isOwner && !caller.isOwner)
		return false;

	auto user = _repo.SpaceUser().GetByIds(updUser.spaceId, updUser.userId);

	// У владельца ничего менять нельзя
	if (user.isOwner)
		return false;

	if (updUser.isOwner) {
		auto trx = _repo.WithTrx();
		trx.SpaceUser().SetIsOwner(updUser.spaceId, caller.userId, /*isOwner*/ false);
		trx.SpaceUser().SetIsOwner(updUser.spaceId, updUser.userId, /*isOwner*/ true);
		trx.Commit();
		return true;
	}

	user.roleId = updUser.roleId;
	_repo.SpaceUser().Update(user);
	return true;
}

const std::string& Service::GetJSONSchemasPath() {
	return _jsonSchemasPath;
}

bool Service::isKeyReserved(const std::string& key) {
	static const std::set<std::string> reserved{
		"u", "auth", "settings", "main", "api"
	};
	return reserved.contains(key);
}

tokens::Tokens& Service::Tokens() {
	return _tokens;
}

std::pair<model::Space, int> Service::GetSpaceAndRoleId(const std::string& key, const std::string userId) {
	model::Space space = _repo.Space().SelectByKey(key);
	model::SpaceUser user = _repo.SpaceUser().GetByIds(space.id, userId);
	std::pair<model::Space, int> res(space, user.roleId);
	return res;
}

std::string Service::GetKeyFromHeader(const std::string& header) {
	static std::regex rgx("^/([^/]+)/");
	std::smatch match;

	if (!std::regex_search(header.begin(), header.end(), match, rgx))
		throw errors::BadRequest400("Space key is missing");

	return match[1];
}

std::string Service::GenerateCookieName(const std::string& key) {
	uint32_t crc32 = generateCRC32(key);
	std::string cookieName = "space_" + std::to_string(crc32);
	return cookieName;
}

std::string Service::CreateToken(const std::string& id, const std::string& key, const std::string& userId, const std::string& roleId) {
	std::string token = Tokens().Create(key, id, roleId, userId, _tokenExpireSecs);
	return token;
}

model::Group Service::GetGroup(int id, const std::string& userId, const boost::uuids::uuid& spaceId) {
	return _repo.Group().Select(id, spaceId);
}

void Service::DeleteGroup(int id, const std::string& userId, const boost::uuids::uuid& spaceId) {
	_repo.Group().Delete(id, spaceId);
}

void Service::CreateGroup(const model::Group& item, const std::string& userId, const boost::uuids::uuid& spaceId) {
	_repo.Group().Create(item, spaceId);
}

void Service::UpdateGroup(const model::Group& item, const std::string& userId, const boost::uuids::uuid& spaceId) {
	_repo.Group().Update(item, spaceId);
}

PagingResult<model::Group> Service::GetGroupList(const std::string& userId, uint32_t start, uint32_t limit, const boost::uuids::uuid& spaceId) {
	return _repo.Group().SelectList(start, limit, spaceId);
}

model::Role Service::GetRole(int id, const std::string& userId, const boost::uuids::uuid& spaceId) {
	return _repo.Role().Select(id, spaceId);
}

void Service::DeleteRole(int id, const std::string& userId, const boost::uuids::uuid& spaceId, bool isAdmin) {
	if (!isAdmin)
		throw errors::Forbidden403();
	_repo.Role().Delete(id, spaceId);
}

void Service::CreateRole(const std::string& roleName, const std::string& userId, const boost::uuids::uuid& spaceId, bool isAdmin) {
	if (!isAdmin)
		throw errors::Forbidden403();
	_repo.Role().Create(roleName, spaceId);
}

void Service::UpdateRole(const model::Role& item, const std::string& userId, const boost::uuids::uuid& spaceId, bool isAdmin) {
	if (!isAdmin)
		throw errors::Forbidden403();
	_repo.Role().Update(item, spaceId);
}

PagingResult<model::Role> Service::GetRoleList(const std::string& userId, uint32_t start, uint32_t limit, const boost::uuids::uuid& spaceId) {
	return _repo.Role().SelectList(start, limit, spaceId);
}

uint32_t Service::generateCRC32(const std::string& data) {
	boost::crc_32_type result;
	result.process_bytes(data.data(), data.length());
	return result.checksum();
}

void Service::createSystemRoles() {
	_repo.Role().CreateSystemRoles();
}

}