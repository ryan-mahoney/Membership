<?php
/**
 * Opine\Membership
 *
 * Copyright (c)2013, 2014 Ryan Mahoney, https://github.com/Opine-Org <ryan@virtuecenter.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Opine;

class Membership {
	private $db;
	private $mail;
	private $handlebars;
	private $userFields = ['first_name', 'last_name', 'middle_name', 'suffix', 'prefix', 'email', 'membership'];
	private $messageExpirationPending;
	private $messageExpiration;

	public function __construct ($db, $mail) {
		$this->db = $db;
		$this->mail = $mail;
	}

	public function userCheck ($userId) {
		$this->db->collection('users')->findOne();
	}

	public function userJoinOrExtend ($userId, $tierId, $term='+1 year', $lifetime=false) {
		$record['lifetime'] = $lifetime;
		$expiration = new \DateTime();
		$expiration->modify($term);
		$record['expiration'] = new \MongoDate($expiration->format('U'));
		$record['term'] = $term;
		$record['year'] = [date('Y')];
		$record['tierId'] = $tierId;
		$record['status'] = 'active';
		$record['_id'] = new \MongoId();
		$recordExisting = $this->userCheck($userId);
		if (isset($recordExisting['_id'])) {
			$record['_id'] = $recordExisting['_id'];
			$expirationExisting = new \DateTime();
			$expirationExisting->setTimestamp($recordExisting['expiration']->sec);
			$expirationExisting->modify($term);
			if ($expirationExisting->format('U') > $expiration->format('U')) {
				$record['expiration'] = new \MongoDate($expirationExisting->format('U'));
			}
		}
		$this->db->collection('users')->update(
			['_id' => $this->db->id($userId)],
			[
				'$set' => [
					'membership' => $record
				],
				'$addToSet' => ['groups' => 'Membership']
			]
		);
	}

	public function userMarkLapsed ($userId) {
		$this->db->collection('users')->update(
			['_id' => $this->db->id($userId)],
			[
				'$set' => [
					'membership.status' => 'lapsed'
				],
				'$pull' => ['groups' => 'Membership']
			]
		);
	}

	public function usersCheckCompliance () {
		$this->db->each(
			$this->db->collection('users')->find(
				[
					'membership.lifetime' => false,
					'membership.status' => 'active',
					'membership.expiration' => ['$lt' => new \MongoDate(strtotime('now'))] 
				],
				[
					$this->userFields
				]
			)->snapshot(), 
			function ($user) {
				$this->notifyExpiration($user);
			}
		);
	}

	private function notificationsSet () {
		$message = $this->db->collection('messages')->findOne(['tags' => 'membership-expiration-pending']);
		if (isset($message['_id'])) {

		}
	}

	private function notifyExpirationPending ($user) {

	}

	private function notifyExpiration ($user) {

	}

	public function notifyWelcome ($user) {
		$this->notificationsSet();
	}
}