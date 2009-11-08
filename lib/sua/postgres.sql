/*
 * Users
*/

CREATE TABLE t_users (
	c_user TEXT PRIMARY KEY,
	c_password_sha1 TEXT,
	c_password_reset_hash TEXT, -- Wenn die „Passwort vergessen“-Funktion getätigt wurde, steht hier der Kontrollstring
	c_registration TIMESTAMP, -- Zeitstempel der Registrierung
	c_last_activity TIMESTAMP, -- Zeitstempel der letzten Aktivität
	c_flightexp BIGINT DEFAULT 0, -- Flugerfahrungspunkte
	c_battleexp BIGINT DEFAULT 0, -- Kampferfahrungspunkte
	c_used_ress_0 BIGINT DEFAULT 0, -- Insgesamt ausgegebenes Carbon
	c_used_ress_1 BIGINT DEFAULT 0, -- ~ Aluminium
	c_used_ress_2 BIGINT DEFAULT 0, -- ~ Wolfram
	c_used_ress_3 BIGINT DEFAULT 0, -- ~ Radium
	c_used_ress_4 BIGINT DEFAULT 0, -- ~ Tritium
	c_description TEXT, -- Selbst eingegebene Benutzerbeschreibung
	c_description_parsed TEXT, -- FormattedString::parseHTML() auf die Benutzerbeschreibung
	c_locked TIMESTAMP, -- -1: Gesperrt auf Weiteres; 0: entsperrt; alles andere: Zeitstempel, bis wann die Sperre gilt
	c_holidays BOOLEAN, -- 0: Kein Urlaubsmodus; 1: Urlaubsmodus
	c_holidays_changed TIMESTAMP, -- Zeitstempel, wann der Urlaubsstatus zuletzt geändert wurde
	c_last_inactivity_mail TIMESTAMP, -- Zeitstempel, wann die letzte Inaktivitätswarnung verschickt wurde
	c_last_request_uri TEXT, -- REQUEST_URI des letzten Aufrufs, wird bei Anmeldung wieder aufgerufen
	c_next_challenge TIMESTAMP, -- Zeitstempel der nächsten Captcha-Abfrage
	c_challenge_failures INTEGER DEFAULT 0 -- Wie oft hintereinander wurde das Captcha falsch eingegeben?
);

CREATE TABLE t_planets (
	c_galaxy INTEGER,
	c_system INTEGER,
	c_planet INTEGER,
	c_size_original INTEGER DEFAULT 0,
	c_user TEXT REFERENCES t_users,
	c_name TEXT,
	c_ress0 DOUBLE PRECISION DEFAULT 0,
	c_ress1 DOUBLE PRECISION DEFAULT 0,
	c_ress2 DOUBLE PRECISION DEFAULT 0,
	c_ress3 DOUBLE PRECISION DEFAULT 0,
	c_ress4 DOUBLE PRECISION DEFAULT 0,
	c_last_refresh TIMESTAMP,
	c_tf0 DOUBLE PRECISION DEFAULT 0,
	c_tf1 DOUBLE PRECISION DEFAULT 0,
	c_tf2 DOUBLE PRECISION DEFAULT 0,
	c_tf3 DOUBLE PRECISION DEFAULT 0,
	c_size BIGINT DEFAULT 0,
	c_user_index INTEGER, -- Bestimmt die Reihenfolge der Planeten eines Benutzers
	PRIMARY KEY ( c_galaxy, c_system, c_planet )
);

CREATE INDEX i_planets_user ON t_planets ( c_user );

CREATE TABLE t_users_research ( -- Erlangte Forschungen der Benutzer
	c_user TEXT REFERENCES t_users,
	c_id TEXT,
	c_level BIGINT DEFAULT 0,
	c_scores DOUBLE PRECISION DEFAULT 0,
	PRIMARY KEY ( c_user, c_id )
);

CREATE TABLE t_users_friends ( -- Verbündetenbeziehungen
	c_user1 TEXT REFERENCES t_users,
	c_user2 TEXT REFERENCES t_users,
	PRIMARY KEY ( c_user1, c_user2 )
);

CREATE TABLE t_users_friend_requests ( -- Verbündetenanfragen
	c_user_from TEXT REFERENCES t_users,
	c_user_to TEXT REFERENCES t_users,
	PRIMARY KEY ( c_user_from, c_user_to )
);

CREATE TABLE t_users_shortcuts ( -- Planeten-„Lesezeichen“
	c_user TEXT REFERENCES t_users,
	c_galaxy INTEGER,
	c_system INTEGER,
	c_planet INTEGER,
	c_i INTEGER,
	FOREIGN KEY ( c_galaxy, c_system, c_planet ) REFERENCES t_planets
);

CREATE TABLE t_users_settings ( -- Einstellungen
	c_user TEXT REFERENCES t_users,
	c_setting TEXT NOT NULL,
	c_value TEXT
);

CREATE TABLE t_users_email ( -- E-Mail-Adressen der Benutzer, alte Einstellungen werden gespeichert
	c_user TEXT REFERENCES t_users,
	c_email TEXT,
	c_valid_from TIMESTAMP
);

CREATE TABLE t_users_openid ( -- OpenID-Accounts, die zu Benutzern gehören
	c_user TEXT REFERENCES t_users,
	openid TEXT NOT NULL
);

/*
 * Planets
*/

CREATE TABLE t_planets_items (
	c_galaxy INTEGER,
	c_system INTEGER,
	c_planet INTEGER,
	c_id TEXT,
	c_type TEXT,
	c_level BIGINT DEFAULT 0,
	c_scores BIGINT DEFAULT 0,
	c_fields BIGINT DEFAULT 0,
	c_prod_factor REAL DEFAULT 1 NOT NULL,
	FOREIGN KEY ( c_galaxy, c_system, c_planet ) REFERENCES t_planets,
	PRIMARY KEY ( c_galaxy, c_system, c_planet, c_type, c_id )
);

CREATE TABLE t_planets_building ( -- Gerade bauende Gegenstände
	c_galaxy INTEGER,
	c_system INTEGER,
	c_planet INTEGER,
	c_id TEXT, -- Item-ID
	c_type TEXT, -- gebaeude, forschung, roboter, schiffe, verteidigung
	c_number BIGINT NOT NULL, -- Anzahl der zu bauenden Roboter, Schiffe oder Verteidigungsanlagen dieses Typs (bei Gebäuden auch -1)
	c_start TIMESTAMP NOT NULL, -- Zeitstempel, wann der Bau gestartet wurde
	c_duration DOUBLE PRECISION NOT NULL, -- Bauzeit eines einzelnen Gegenstandes
	c_cost0 BIGINT DEFAULT 0, -- Ausgegebene Kosten, Carbon (wissenswert bei Abbruch von Gebäude oder Forschung
	c_cost1 BIGINT DEFAULT 0, -- Kosten, Aluminium
	c_cost2 BIGINT DEFAULT 0, -- Kosten, Wolfram
	c_cost3 BIGINT DEFAULT 0, -- Kosten, Radium
	c_global BOOLEAN DEFAULT false, -- Bei Forschung: 1: Es wird global geforscht
	FOREIGN KEY ( c_galaxy, c_system, c_planet ) REFERENCES t_planets
);

CREATE INDEX i_planets_building_type ON t_planets_building ( c_galaxy, c_system, c_planet, c_type );

CREATE VIEW t_planets_items_user AS SELECT
	t_planets_items.c_galaxy AS c_galaxy,
	t_planets_items.c_system AS c_system,
	t_planets_items.c_planet AS c_planet,
	t_planets_items.c_id AS c_id,
	t_planets_items.c_type AS c_type,
	t_planets_items.c_level AS c_level,
	t_planets_items.c_scores AS c_scores,
	t_planets_items.c_fields AS c_fields,
	t_planets_items.c_prod_factor AS c_prod_factor,
	t_planets.c_user AS c_user
	FROM t_planets_items INNER JOIN (
		SELECT c_galaxy, c_system, c_planet, c_user FROM t_planets
	) AS t_planets ON t_planets_items.c_galaxy = t_planets.c_galaxy AND t_planets_items.c_system = t_planets.c_system AND t_planets_items.c_planet = t_planets.c_planet
;

CREATE VIEW t_galaxies AS SELECT
	c_galaxy,
	COUNT(DISTINCT c_system) AS c_systems
	FROM t_planets GROUP BY c_galaxy
;

CREATE VIEW t_systems AS SELECT
	c_galaxy || ':' || c_system AS c_id,
	COUNT(DISTINCT c_planet) AS c_planets
	FROM t_planets GROUP BY c_id
;

/*
 * Fleets
*/

CREATE TABLE t_fleets (
	c_fleet_id TEXT PRIMARY KEY,
	c_password TEXT
);

CREATE TABLE t_fleets_targets (
	c_fleet_id TEXT REFERENCES t_fleets,
	c_i INTEGER,
	c_galaxy INTEGER,
	c_system INTEGER,
	c_planet INTEGER,
	c_type INTEGER,
	c_flying_back BOOLEAN DEFAULT false,
	c_arrival TIMESTAMP,
	c_finished BOOLEAN DEFAULT false,
	FOREIGN KEY ( c_galaxy, c_system, c_planet ) REFERENCES t_planets,
	PRIMARY KEY ( c_fleet_id, c_i )
);

CREATE TABLE t_fleets_users (
	c_fleet_id TEXT REFERENCES t_fleets,
	c_user TEXT REFERENCES t_users,
	c_i INTEGER,
	c_from_galaxy INTEGER,
	c_from_system INTEGER,
	c_from_planet INTEGER,
	c_factor REAL DEFAULT 1,
	c_ress0 BIGINT DEFAULT 0,
	c_ress1 BIGINT DEFAULT 0,
	c_ress2 BIGINT DEFAULT 0,
	c_ress3 BIGINT DEFAULT 0,
	c_ress4 BIGINT DEFAULT 0,
	c_ress_tritium BIGINT DEFAULT 0,
	c_hress0 BIGINT DEFAULT 0, -- FIXME: Shouldn’t these be separated for each target?
	c_hress1 BIGINT DEFAULT 0,
	c_hress2 BIGINT DEFAULT 0,
	c_hress3 BIGINT DEFAULT 0,
	c_hress4 BIGINT DEFAULT 0,
	c_used_tritium BIGINT DEFAULT 0,
	c_dont_put_ress BOOLEAN DEFAULT false, -- FIXME: Shouldn’t this be separated for each target?
	c_departing BOOLEAN DEFAULT false,
	FOREIGN KEY ( c_from_galaxy, c_from_system, c_from_planet ) REFERENCES t_planets,
	PRIMARY KEY ( c_fleet_id, c_user )
);

CREATE INDEX i_fleets_users ON t_fleets_users ( c_user );

CREATE TABLE t_fleets_users_rob (
	c_fleet_id TEXT,
	c_user TEXT,
	c_id TEXT,
	c_number BIGINT DEFAULT 0,
	c_scores BIGINT DEFAULT 0,
	FOREIGN KEY ( c_fleet_id, c_user ) REFERENCES t_fleets_users,
	PRIMARY KEY ( c_fleet_id, c_user, c_id )
);

CREATE TABLE t_fleets_users_hrob (
	c_fleet_id TEXT,
	c_user TEXT,
	c_id TEXT,
	c_number BIGINT DEFAULT 0,
	c_scores BIGINT DEFAULT 0,
	FOREIGN KEY ( c_fleet_id, c_user ) REFERENCES t_fleets_users,
	PRIMARY KEY ( c_fleet_id, c_user, c_id )
);

CREATE TABLE t_fleets_users_fleet (
	c_fleet_id TEXT,
	c_user TEXT,
	c_id TEXT,
	c_number BIGINT DEFAULT 0,
	c_scores BIGINT DEFAULT 0,
	FOREIGN KEY ( c_fleet_id, c_user ) REFERENCES t_fleets_users,
	PRIMARY KEY ( c_fleet_id, c_user, c_id )
);

CREATE VIEW t_highscores AS SELECT
	t_users.c_user AS c_user,
	t_gebaeude.c_scores AS c_gebaeude,
	t_forschung.c_scores AS c_forschung,
	t_roboter.c_scores AS c_roboter,
	t_schiffe.c_scores AS c_schiffe,
	t_verteidigung.c_scores AS c_verteidigung,
	t_users.c_flightexp AS c_flightexp,
	t_users.c_battleexp AS c_battleexp,
	t_gebaeude.c_scores + t_forschung.c_scores + t_roboter.c_scores + t_schiffe.c_scores + t_verteidigung.c_scores + t_users.c_flightexp + t_users.c_battleexp AS c_total
	FROM (
		SELECT c_user, c_flightexp, c_battleexp FROM t_users
	) AS t_users LEFT OUTER JOIN (
		SELECT c_user,SUM(c_scores) AS c_scores FROM t_planets_items_user WHERE c_type = 'gebaeude' GROUP BY c_user
	) AS t_gebaeude ON t_gebaeude.c_user = t_users.c_user LEFT OUTER JOIN (
		SELECT c_user,SUM(c_scores) AS c_scores FROM t_users_research GROUP BY c_user
	) AS t_forschung ON t_forschung.c_user = t_users.c_user LEFT OUTER JOIN (
		SELECT c_user,SUM(c_scores) AS c_scores FROM (
			SELECT c_user,c_scores FROM t_planets_items_user WHERE c_type = 'roboter'
			UNION ALL SELECT c_user,c_scores FROM t_fleets_users_rob
			UNION ALL SELECT c_user,c_scores FROM t_fleets_users_hrob
		) AS t_roboter GROUP BY c_user
	) AS t_roboter ON t_roboter.c_user = t_users.c_user LEFT OUTER JOIN (
		SELECT c_user,SUM(c_scores) AS c_scores FROM (
			SELECT c_user,c_scores FROM t_planets_items_user WHERE c_type = 'schiffe'
			UNION ALL SELECT c_user,c_scores FROM t_fleets_users_fleet
		) AS t_schiffe GROUP BY c_user
	) AS t_schiffe ON t_schiffe.c_user = t_users.c_user LEFT OUTER JOIN (
		SELECT c_user,SUM(c_scores) AS c_scores FROM t_planets_items_user WHERE c_type = 'verteidigung' GROUP BY c_user
	) AS t_verteidigung ON t_verteidigung.c_user = t_users.c_user
;

/*
 * Alliances
*/

CREATE TABLE t_alliances (
	c_tag TEXT PRIMARY KEY,
	c_name TEXT,
	c_description TEXT,
	c_description_parsed TEXT,
	c_inner_description TEXT,
	c_inner_description_parsed TEXT,
	c_last_rename TIMESTAMP,
	c_allow_applications BOOLEAN DEFAULT true
);

CREATE INDEX i_alliances_name ON t_alliances ( c_name );

CREATE TABLE t_alliances_members (
	c_tag TEXT REFERENCES t_alliances ON UPDATE CASCADE,
	c_member TEXT REFERENCES t_users ON UPDATE CASCADE,
	c_rank TEXT,
	c_time TIMESTAMP,
	c_permissions INTEGER DEFAULT 0,
	PRIMARY KEY (c_tag, c_member)
);

CREATE INDEX i_alliances_members_permissions ON t_alliances_members ( c_tag, c_member, c_permissions );

CREATE TABLE t_alliances_applications (
	c_tag TEXT REFERENCES t_alliances ON UPDATE CASCADE,
	c_user TEXT REFERENCES t_users ON UPDATE CASCADE,
	PRIMARY KEY (c_tag, c_user)
);

CREATE VIEW t_alliance_highscores_sum AS SELECT
	t_allianceh.c_tag AS c_tag,
	t_alliances.c_name AS c_name,
	t_allianceh.c_gebaeude AS c_gebaeude,
	t_allianceh.c_forschung AS c_forschung,
	t_allianceh.c_roboter AS c_roboter,
	t_allianceh.c_schiffe AS c_schiffe,
	t_allianceh.c_verteidigung AS c_verteidigung,
	t_allianceh.c_flightexp AS c_flightexp,
	t_allianceh.c_battleexp AS c_battleexp,
	t_allianceh.c_gebaeude + t_allianceh.c_forschung + t_allianceh.c_roboter + t_allianceh.c_schiffe + t_allianceh.c_verteidigung + t_allianceh.c_flightexp + t_allianceh.c_battleexp AS c_total
	FROM ( SELECT
		t_alliances_members.c_tag AS c_tag,
		COUNT(DISTINCT t_userh.c_user) AS c_members,
		SUM(t_userh.c_gebaeude) AS c_gebaeude,
		SUM(t_userh.c_forschung) AS c_forschung,
		SUM(t_userh.c_roboter) AS c_roboter,
		SUM(t_userh.c_schiffe) AS c_schiffe,
		SUM(t_userh.c_verteidigung) AS c_verteidigung,
		SUM(t_userh.c_flightexp) AS c_flightexp,
		SUM(t_userh.c_battleexp) AS c_battleexp
		FROM t_alliances_members LEFT OUTER JOIN (
			SELECT c_user, c_gebaeude, c_forschung, c_roboter, c_schiffe, c_verteidigung, c_flightexp, c_battleexp FROM t_highscores
		) AS t_userh ON t_alliances_members.c_member = t_userh.c_user
		GROUP BY t_alliances_members.c_tag
	) AS t_allianceh LEFT OUTER JOIN (
		SELECT c_tag, c_name FROM t_alliances
	) AS t_alliances ON t_allianceh.c_tag = t_alliances.c_tag
;

CREATE VIEW t_alliance_highscores_average AS SELECT
	t_allianceh.c_tag AS c_tag,
	t_alliances.c_name AS c_name,
	t_allianceh.c_gebaeude AS c_gebaeude,
	t_allianceh.c_forschung AS c_forschung,
	t_allianceh.c_roboter AS c_roboter,
	t_allianceh.c_schiffe AS c_schiffe,
	t_allianceh.c_verteidigung AS c_verteidigung,
	t_allianceh.c_flightexp AS c_flightexp,
	t_allianceh.c_battleexp AS c_battleexp,
	t_allianceh.c_gebaeude + t_allianceh.c_forschung + t_allianceh.c_roboter + t_allianceh.c_schiffe + t_allianceh.c_verteidigung + t_allianceh.c_flightexp + t_allianceh.c_battleexp AS c_total
	FROM ( SELECT
		t_alliances_members.c_tag AS c_tag,
		COUNT(DISTINCT t_userh.c_user) AS c_members,
		AVG(t_userh.c_gebaeude) AS c_gebaeude,
		AVG(t_userh.c_forschung) AS c_forschung,
		AVG(t_userh.c_roboter) AS c_roboter,
		AVG(t_userh.c_schiffe) AS c_schiffe,
		AVG(t_userh.c_verteidigung) AS c_verteidigung,
		AVG(t_userh.c_flightexp) AS c_flightexp,
		AVG(t_userh.c_battleexp) AS c_battleexp
		FROM t_alliances_members LEFT OUTER JOIN (
			SELECT c_user, c_gebaeude, c_forschung, c_roboter, c_schiffe, c_verteidigung, c_flightexp, c_battleexp FROM t_highscores
		) AS t_userh ON t_alliances_members.c_member = t_userh.c_user
		GROUP BY t_alliances_members.c_tag
	) AS t_allianceh LEFT OUTER JOIN (
		SELECT c_tag, c_name FROM t_alliances
	) AS t_alliances ON t_allianceh.c_tag = t_alliances.c_tag
;

/*
 * Messages
*/

CREATE TABLE t_messages (
	c_message_id TEXT PRIMARY KEY,
	c_time TIMESTAMP,
	c_text TEXT,
	c_parsed_text TEXT,
	c_sender TEXT REFERENCES t_users,
	c_subject TEXT,
	c_html BOOLEAN DEFAULT false
);

CREATE TABLE t_messages_users (
	c_message_id TEXT REFERENCES t_messages,
	c_user TEXT REFERENCES t_users,
	c_type INTEGER,
	c_status INTEGER,
	PRIMARY KEY ( c_user, c_message_id )
);

CREATE TABLE t_messages_messages_recipients (
	c_message_id TEXT REFERENCES t_messages,
	c_recipient TEXT REFERENCES t_users,
	PRIMARY KEY ( c_recipient, c_message_id )
);

CREATE TABLE t_public_messages (
	c_message_id TEXT PRIMARY KEY,
	c_last_view TIMESTAMP,
	c_sender TEXT,
	c_text TEXT,
	c_parsed TEXT,
	c_subject TEXT,
	c_html BOOLEAN DEFAULT false,
	c_receiver TEXT,
	c_time TIMESTAMP,
	c_type INTEGER
);

/*
 * Market
*/

CREATE TABLE t_market (
	c_id BIGINT PRIMARY KEY,
	c_galaxy INTEGER,
	c_system INTEGER,
	c_user TEXT REFERENCES t_users,
	c_planet INTEGER,
	c_offered_resource INTEGER,
	c_amount BIGINT,
	c_requested_resource INTEGER,
	c_min_price REAL,
	c_expiration INTERVAL,
	c_date TIMESTAMP,
	c_finish TIMESTAMP,
	FOREIGN KEY ( c_galaxy, c_system, c_planet ) REFERENCES t_planets
);

CREATE INDEX i_market_offered_requested ON t_market ( c_offered_resource, c_requested_resource );
CREATE INDEX i_market_offered_requested_price ON t_market ( c_offered_resource, c_requested_resource, c_min_price );
CREATE INDEX i_market_planet ON t_market ( c_galaxy, c_system, c_planet );

CREATE TABLE t_market_rate (
	c_offer INTEGER,
	c_request INTEGER,
	c_price REAL,
	c_date TIMESTAMP,
	PRIMARY KEY ( c_offer, c_request )
);