# Changelog - API

### 2.0.4 (2025-08-24)

- Fix signup 

### 2.0.3 (2025-08-24)

- Add 8 new categories
  - Application
  - Astronomy
  - Character
  - Clothing
  - Mathematical
  - Mineralogy
  - Plant

### 2.0.2 (2025-08-22)

- Fix get history
- Fix OAuth new account

### 2.0.1 (2025-08-21)

- Fix profile path

### 2.0.0 (2025-08-20)

- Update to **Symfony** `7.3`
- Update to **Api-plateform** `4.1`
- Update to **Doctrine/Orm** `3.0`
- Test: **Postman** → **Bruno**

---

### 1.32.1 (2025-08-08)

- Add community category
- Fix week (ISO: start on Monday)

### 1.32.0 (2025-06-06)

- Add search by tag and children
- Add itemImageCover:opti

### 1.31.0 (2025-05-03)

- Admin: add stats

### 1.30.1 (2025-04-27)

- Fix username or email on login

### 1.30.0 (2025-03-18)

- Add transparent color in pattern
- Add an adult tag to the rankings
- Fix color pattern in schemas

### 1.21.1 (2024-12-30)

- Add test page
- Add Vtuber category
- Fix theme for non-logged in user

### 1.21.0 (2024-11-08)

- Add columns mode
- Fix theme schema

### 1.20.1 (2024-10-30)

- Fix classement & theme schema

### 1.20.1 (2024-10-21)

- Add themes
- Add Json schema validator
- Fix background image in db
- Tests Postman: add themes

### 1.15.0 (2024-10-30)

- Add current in history

### 1.14.1 (2023-10-22)

- Add Category: bingo

### 1.14.0 (2024-07-16)

- Fix read classement not hidden with password (ignore this)
- Support multi-domain (for domain change)
- Fix the link in email for sign-up
- Fix classement without images

### 1.13.0 (2024-01-15)

- Add search mode in navigation
- Add sitemap

### 1.12.3 (2023-10-22)

- Fix bug when avatar with no classement
- Add 2 new modes (Iceberg & Axis)

### 1.12.2 (2023-09-15)

- Strange bug with id in url
- Fix private

### 1.12.1 (2023-08-31)

- Fix history information on update

### 1.12.0 (2023-08-27)

- Add username change
- Postman json update

### 1.11.2 (2023-08-27)

- Fix tags search

### 1.11.1 (2023-08-12)

- Add withHistory on getUser & getClassement
- Fixed reading a history tierlist

### 1.11.0 (2023-06-22)

- Add mode Teams
- Add linkId with test
- Add page size parameter
- Fix mode teams (supports tiles with only ids)
- Fix request for get Classement
- Fix Classement with no linkId
- Postman json update

### 1.0.10 (2023-05-28)

- User: fix test for category change
- Add category change
- Delete unuse info in Json data
- Admin: sorting user & tierlist
- Admin: filter for users and tierlists
- Add postman tests

### 1.0.9 (2023-05-17)

- Add tags support
- Implementation of avatar management
- Fix new profile
- Update postman collection

### 1.0.8 (2023-03-26)

- Add history

### 1.0.7 (2022-12-28)

- Add custom background image
- Add entertainment and place categories

### 1.0.6 (2022-11-11)

- Return email for current user
- Add password on classement
- Add password status (true / false)
- Fix CORS
- Minor fix for laster tierlists
- Update postman tests

### 1.0.5 (2022-11-08)

- Add last tierlists
- Add status change for user tierlists
- Add missing user info
- Fix order for last tierlists

### 1.0.4 (2022-11-01)

- Update catagory is parent template
- Parent status is required
- Fix crash on derivative
- Update tests Postman

### 1.0.3 (2022-10-29)

- Improve paginated search
- Remove invalide case in tierlist search
- Fix derivatives when empty search (but not null)
- Fix page size
- Add categories: food, brand, role-playing

### 1.0.2 (2022-10-22)

- Show hidden tierlist for current user
- Search by template and userId
- Add total ranking on save
- Add date change
- No path if no image
- Fix crash on create
- Fix crash when user not validate Oauth

### 1.0.1 (2022-09-11)

- Add counters for navigation
- Change order of result by categories
- Fix account without password
- More categories
- Postman tests
- Fix CORS

### 1.0.0 (2022-08-30)

- first version with:
  - user management
  - classement save
  - oauth2
- **Symfony** `6.0`
- **Api-plateform** `2.6`
- **Doctrine/Orm** `2.11`
