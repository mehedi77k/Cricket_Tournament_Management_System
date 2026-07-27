# Cricket Tournament Management System

A web-based Cricket Tournament Management System developed with PHP, MySQL, HTML, CSS, and JavaScript. The application provides tournament administration, team and player management, match scheduling, automatic winner calculation, player performance tracking, award management, points-table calculation, and role-based user authentication.

## Project Overview

The system is designed to simplify the management of small and medium-sized cricket tournaments. Administrators can maintain tournament records through a responsive dashboard, while registered users can view match results, tournament statistics, and the current points table.

Match winners are calculated automatically from the total scores of both teams. The points table is then recalculated using the configured tournament rules.

## Main Features

### Authentication and User Profile

- Separate Admin and User accounts
- One-time first Admin account setup
- Public User registration
- Shared login page for Admin and User
- Session-based authentication
- Secure password hashing
- User profile update
- Password change functionality
- Account status validation
- Secure logout using CSRF protection

### Admin Dashboard

- Total number of teams
- Total number of players
- Total number of matches
- Total number of assigned awards
- Recent match results
- Current tournament standings
- Leading run scorer
- Leading wicket taker

### Team Management

- Add new teams
- Edit existing teams
- Delete teams
- Store team name and captain name
- View the number of players in each team
- Search team records

### Player Management

- Add players to teams
- Edit player information
- Delete players
- Assign player roles
- Search and filter player records

Supported player roles:

- Batsman
- Bowler
- All-Rounder
- Wicket Keeper

### Match Management

- Schedule matches between two different teams
- Store match date
- Enter Team 1 total score
- Enter Team 2 total score
- Automatically calculate the winning team
- Support pending, completed, and draw results
- Edit and delete match records
- Search matches by team, date, score, or winner

### Automatic Match Result

The match result is calculated according to the following rules:

| Condition | Result |
|---|---|
| Both scores are empty | Match remains pending |
| Team 1 score is higher | Team 1 wins |
| Team 2 score is higher | Team 2 wins |
| Both scores are equal | Match is declared a draw |

Example:

```text
Team 1: Storm Breakers
Team 1 Score: 120

Team 2: Byte Blasters
Team 2 Score: 125

Result: Byte Blasters won
```

### Player Score Management

- Record individual player runs
- Record individual player wickets
- Select players according to the selected match
- Prevent players from unrelated teams from being selected
- Edit and delete score records
- Filter scores by match

Team totals and individual player scores are stored separately. This allows team totals to include extras that are not counted as an individual player's runs.

### Points Table

The points table is automatically recalculated after a match is added, edited, or deleted.

| Match Result | Points |
|---|---:|
| Win | 2 |
| Draw | 1 |
| Loss | 0 |

The standings contain:

- Matches played
- Wins
- Losses
- Draws
- Total points

Teams are ranked using the following order:

1. Total points
2. Total wins
3. Total draws
4. Team name

### Award Management

- Create custom award types
- Activate or deactivate award types
- Assign awards to players
- Select eligible players based on the selected match
- Store performance values
- Store performance units
- Add remarks for award decisions
- Edit and delete assigned awards

Example award types:

- Man of the Match
- Best Batsman
- Best Bowler
- Fastest Bowler

### User Dashboard

Registered users can view:

- Tournament summary
- Total teams
- Total players
- Total matches
- Total awards
- Recent match results
- Team scores
- Match winners
- Pending matches
- Drawn matches
- Current points table
- Personal profile

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.1+ | Server-side application logic |
| MySQL or MariaDB | Relational database |
| PDO | Secure database communication |
| HTML5 | Page structure |
| CSS3 | Responsive interface design |
| JavaScript | Search, confirmation, menu, and dynamic form features |
| Apache | Local web server |
| Laragon, XAMPP, or WAMP | Local development environment |

No PHP framework, Composer package, or npm package is required.

## System Requirements

Before installing the project, make sure the following software is available:

- PHP 8.1 or later
- MySQL 5.7+, MySQL 8+, or a compatible MariaDB version
- Apache or Nginx web server
- PDO MySQL PHP extension
- A modern web browser
- Laragon, XAMPP, WAMP, or a similar local server package

## Installation

### 1. Download or Clone the Project

Using Git:

```bash
git clone https://github.com/mehedi77k/Cricket_Tournament_Management_System.git cricket_tournament_app
```

For Laragon, place the project inside:

```text
C:\laragon\www\cricket_tournament_app
```

For XAMPP, place it inside:

```text
C:\xampp\htdocs\cricket_tournament_app
```

### 2. Start the Required Services

Start the following services from Laragon, XAMPP, or WAMP:

- Apache
- MySQL

### 3. Create the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Create a database named:

```sql
CREATE DATABASE IF NOT EXISTS cricket_tournament_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

### 4. Import the Database

Select the `cricket_tournament_db` database and import the latest project SQL file.

Example database file name:

```text
cricket_tournament_db.sql
```

The database should contain the following tables:

| Table | Purpose |
|---|---|
| `users` | Admin and User account information |
| `team` | Tournament team information |
| `player` | Player information and team assignment |
| `matches` | Match schedule, team scores, and result |
| `score` | Individual player runs and wickets |
| `award_type` | Available award categories |
| `match_award` | Awards assigned to players |
| `points_table` | Calculated tournament standings |

The `matches` table must contain these result-related fields:

```text
team1_score
team2_score
result_status
winner_team_id
```

The `points_table` must contain:

```text
matches_played
wins
losses
draws
points
```

### 5. Configure the Database Connection

Open:

```text
config/db.php
```

Default configuration:

```php
<?php

$DB_HOST = 'localhost';
$DB_NAME = 'cricket_tournament_db';
$DB_USER = 'root';
$DB_PASS = '';
```

Update these values according to your MySQL server configuration.

For a default Laragon or XAMPP installation, the username is usually `root` and the password is usually empty.

### 6. Create the First Admin

Open the following address:

```text
http://localhost/cricket_tournament_app/setup_admin.php
```

Enter:

- Admin full name
- Admin email
- Phone number
- Password
- Password confirmation

The password must:

- Contain at least 8 characters
- Contain at least one letter
- Contain at least one number

After the first Admin is created, the setup page prevents another first-Admin registration.

### 7. Login

Open:

```text
http://localhost/cricket_tournament_app/login.php
```

After login:

- Admin accounts are redirected to `index.php`
- User accounts are redirected to `user_dashboard.php`

### 8. Register a User

Open:

```text
http://localhost/cricket_tournament_app/register.php
```

Accounts created through public registration receive the `user` role automatically.

## Recommended Usage Order

For correct tournament operation, use the system in the following order:

1. Create the first Admin account.
2. Login as Admin.
3. Add tournament teams.
4. Add players and assign them to teams.
5. Create award types.
6. Schedule matches.
7. Enter both team totals after a match finishes.
8. Record individual player runs and wickets.
9. Assign match awards.
10. Review the automatically calculated points table.

## Project Structure

```text
cricket_tournament_app/
│
├── api/
│   └── players_by_match.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── app.js
│
├── config/
│   ├── database.php
│   └── db.php
│
├── includes/
│   ├── bootstrap.php
│   ├── footer.php
│   ├── functions.php
│   └── header.php
│
├── award_types.php
├── awards.php
├── index.php
├── login.php
├── logout.php
├── matches.php
├── players.php
├── points.php
├── profile.php
├── register.php
├── scores.php
├── setup_admin.php
├── teams.php
├── user_dashboard.php
├── LICENSE
└── README.md
```

## Important Application Routes

| Page | Purpose |
|---|---|
| `setup_admin.php` | Create the first Admin |
| `login.php` | Admin and User login |
| `register.php` | Public User registration |
| `index.php` | Admin dashboard |
| `user_dashboard.php` | User dashboard |
| `teams.php` | Team management |
| `players.php` | Player management |
| `matches.php` | Match and team-score management |
| `scores.php` | Individual player performance |
| `award_types.php` | Award-category management |
| `awards.php` | Match-award assignment |
| `points.php` | Tournament standings |
| `profile.php` | Profile and password management |
| `logout.php` | Secure logout |

## Security Features

The project includes the following security measures:

- PDO prepared statements
- Disabled emulated prepared statements
- Password hashing using `password_hash()`
- Password verification using `password_verify()`
- CSRF tokens for form submissions
- Session ID regeneration after login and logout
- HTML output escaping
- Email validation
- Account status checking
- Server-side input validation
- Foreign-key-aware database error handling
- Confirmation before destructive actions

## Production Security Notes

This project is primarily configured for local development and educational use.

Before deploying it publicly:

1. Store database credentials in environment variables.
2. Disable detailed database error messages.
3. Enable HTTPS.
4. Add secure PHP session-cookie settings.
5. Add login rate limiting.
6. Add password-reset and email-verification functionality.
7. Back up the database regularly.
8. Remove unused development files.
9. Prevent access to the `config` directory through the web server.
10. Apply `require_admin();` immediately after `bootstrap.php` on every management page and protected API endpoint.

Example:

```php
require_once __DIR__ . '/includes/bootstrap.php';

require_admin();
```

This authorization check should be present in:

```text
teams.php
players.php
matches.php
scores.php
awards.php
award_types.php
points.php
api/players_by_match.php
```

## Troubleshooting

### Database Connection Failed

Check the values inside:

```text
config/db.php
```

Also confirm that:

- MySQL is running
- The database name is correct
- The MySQL username is correct
- The MySQL password is correct
- The PDO MySQL extension is enabled

### Users Table Not Found

Import the authentication database schema and confirm that the `users` table exists inside:

```text
cricket_tournament_db
```

### Setup Admin Page Does Not Open

Confirm that the file exists at:

```text
C:\laragon\www\cricket_tournament_app\setup_admin.php
```

Then open:

```text
http://localhost/cricket_tournament_app/setup_admin.php
```

### PHP Code Appears as Plain Text

Make sure:

- Apache is running
- The project is inside the correct web-server directory
- PHP files start with `<?php`
- The file extension is `.php`, not `.php.txt`
- The project is opened through `http://localhost`, not directly from File Explorer

### Duplicate Column Error During SQL Import

This means a database migration was executed more than once. Do not rerun an `ALTER TABLE` command after the required columns have already been added.

### CSS Changes Are Not Visible

Use a hard refresh:

```text
Ctrl + F5
```

You may also clear the browser cache.

### Match Points Are Incorrect

Open the Points Table page and use the recalculation function. Also verify that completed matches have:

- Both team scores
- A valid `result_status`
- A valid winner for non-draw matches

## Future Improvements

Possible future enhancements include:

- Admin-based User account management
- Password reset through email
- Email verification
- Match overs and wickets tracking
- Net run rate calculation
- Tournament stages and knockout rounds
- Team logos and player photographs
- Live ball-by-ball scoring
- Exportable PDF reports
- CSV and Excel data export
- Match venue and umpire management
- Tournament scheduling
- Notifications
- Audit logs
- REST API
- Mobile application integration

## Contributing

Contributions are welcome.

To contribute:

1. Fork the repository.
2. Create a new feature branch.
3. Make the required changes.
4. Test the application.
5. Commit the changes.
6. Push the branch.
7. Open a pull request.

Example:

```bash
git checkout -b feature/new-feature
git add .
git commit -m "Add new feature"
git push origin feature/new-feature
```

## License

This project is licensed under the MIT License.

See the `LICENSE` file for complete license information.

## Author

**Mehedi Hasan**

GitHub Repository:

```text
https://github.com/mehedi77k/Cricket_Tournament_Management_System
```

---

This project was developed as a structured PHP and MySQL application for managing cricket tournament data, match results, player performance, awards, authentication, and tournament standings.