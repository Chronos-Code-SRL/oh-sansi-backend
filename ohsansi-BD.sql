--
-- PostgreSQL database dump
--

-- Dumped from database version 15.5
-- Dumped by pg_dump version 15.5

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: areas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.areas (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.areas OWNER TO postgres;

--
-- Name: areas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.areas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.areas_id_seq OWNER TO postgres;

--
-- Name: areas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.areas_id_seq OWNED BY public.areas.id;


--
-- Name: audits; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audits (
    id bigint NOT NULL,
    user_type character varying(255),
    user_id bigint,
    event character varying(255) NOT NULL,
    auditable_type character varying(255) NOT NULL,
    auditable_id bigint NOT NULL,
    old_values text,
    new_values text,
    url text,
    ip_address inet,
    user_agent character varying(1023),
    tags character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.audits OWNER TO postgres;

--
-- Name: audits_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.audits_id_seq OWNER TO postgres;

--
-- Name: audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.audits_id_seq OWNED BY public.audits.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: contestant_level_grades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.contestant_level_grades (
    id bigint NOT NULL,
    contestant_id bigint NOT NULL,
    level_grade_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.contestant_level_grades OWNER TO postgres;

--
-- Name: contestant_level_grades_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.contestant_level_grades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.contestant_level_grades_id_seq OWNER TO postgres;

--
-- Name: contestant_level_grades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.contestant_level_grades_id_seq OWNED BY public.contestant_level_grades.id;


--
-- Name: contestants; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.contestants (
    id bigint NOT NULL,
    first_name character varying(50) NOT NULL,
    last_name character varying(50) NOT NULL,
    ci_document character varying(13) NOT NULL,
    gender character(1) NOT NULL,
    school_name character varying(100) NOT NULL,
    department character varying(50) NOT NULL,
    phone_number character varying(8),
    email character varying(100),
    tutor_name character varying(50) NOT NULL,
    tutor_number character varying(8) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    grade character varying(255)
);


ALTER TABLE public.contestants OWNER TO postgres;

--
-- Name: contestants_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.contestants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.contestants_id_seq OWNER TO postgres;

--
-- Name: contestants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.contestants_id_seq OWNED BY public.contestants.id;


--
-- Name: csv_uploads; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.csv_uploads (
    id bigint NOT NULL,
    olympiad_id bigint NOT NULL,
    original_file_name character varying(255) NOT NULL,
    successful_records integer DEFAULT 0 NOT NULL,
    failed_records integer DEFAULT 0 NOT NULL,
    total_records integer DEFAULT 0 NOT NULL,
    file_path character varying(255) NOT NULL,
    error_file_path character varying(255),
    file_size integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    header_errors integer DEFAULT 0 NOT NULL,
    competitor_errors integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.csv_uploads OWNER TO postgres;

--
-- Name: csv_uploads_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.csv_uploads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.csv_uploads_id_seq OWNER TO postgres;

--
-- Name: csv_uploads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.csv_uploads_id_seq OWNED BY public.csv_uploads.id;


--
-- Name: evaluations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.evaluations (
    id bigint NOT NULL,
    score integer,
    description text,
    registration_id bigint NOT NULL,
    olympiad_area_phase_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    status boolean DEFAULT false NOT NULL,
    classification_status character varying(255),
    classification_place character varying(255),
    CONSTRAINT evaluations_classification_place_check CHECK (((classification_place)::text = ANY ((ARRAY['Oro'::character varying, 'Plata'::character varying, 'Bronce'::character varying, 'Mención honorífica'::character varying])::text[]))),
    CONSTRAINT evaluations_classification_status_check CHECK (((classification_status)::text = ANY ((ARRAY['clasificado'::character varying, 'no_clasificado'::character varying, 'descalificado'::character varying])::text[])))
);


ALTER TABLE public.evaluations OWNER TO postgres;

--
-- Name: evaluations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.evaluations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.evaluations_id_seq OWNER TO postgres;

--
-- Name: evaluations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.evaluations_id_seq OWNED BY public.evaluations.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: grades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.grades (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.grades OWNER TO postgres;

--
-- Name: grades_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.grades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.grades_id_seq OWNER TO postgres;

--
-- Name: grades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.grades_id_seq OWNED BY public.grades.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: level_grades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.level_grades (
    id bigint NOT NULL,
    level_id bigint NOT NULL,
    grade_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    olympiad_area_id bigint NOT NULL
);


ALTER TABLE public.level_grades OWNER TO postgres;

--
-- Name: level_grades_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.level_grades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.level_grades_id_seq OWNER TO postgres;

--
-- Name: level_grades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.level_grades_id_seq OWNED BY public.level_grades.id;


--
-- Name: levels; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.levels (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.levels OWNER TO postgres;

--
-- Name: levels_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.levels_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.levels_id_seq OWNER TO postgres;

--
-- Name: levels_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.levels_id_seq OWNED BY public.levels.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: olympiad_area_level_grades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.olympiad_area_level_grades (
    id bigint NOT NULL,
    olympiad_area_id bigint NOT NULL,
    level_grade_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.olympiad_area_level_grades OWNER TO postgres;

--
-- Name: olympiad_area_level_grades_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.olympiad_area_level_grades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.olympiad_area_level_grades_id_seq OWNER TO postgres;

--
-- Name: olympiad_area_level_grades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.olympiad_area_level_grades_id_seq OWNED BY public.olympiad_area_level_grades.id;


--
-- Name: olympiad_area_phase_level_grades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.olympiad_area_phase_level_grades (
    id bigint NOT NULL,
    olympiad_area_phase_id bigint NOT NULL,
    level_grade_id bigint NOT NULL,
    score_cut integer NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    max_score integer,
    status character varying(255) DEFAULT 'Sin empezar'::character varying NOT NULL,
    CONSTRAINT olympiad_area_phase_level_grades_status_check CHECK (((status)::text = ANY ((ARRAY['Sin empezar'::character varying, 'Activa'::character varying, 'Terminada'::character varying])::text[])))
);


ALTER TABLE public.olympiad_area_phase_level_grades OWNER TO postgres;

--
-- Name: olympiad_area_phase_level_grades_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.olympiad_area_phase_level_grades_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.olympiad_area_phase_level_grades_id_seq OWNER TO postgres;

--
-- Name: olympiad_area_phase_level_grades_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.olympiad_area_phase_level_grades_id_seq OWNED BY public.olympiad_area_phase_level_grades.id;


--
-- Name: olympiad_area_phases; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.olympiad_area_phases (
    id bigint NOT NULL,
    olympiad_area_id bigint NOT NULL,
    phase_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.olympiad_area_phases OWNER TO postgres;

--
-- Name: olympiad_area_phases_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.olympiad_area_phases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.olympiad_area_phases_id_seq OWNER TO postgres;

--
-- Name: olympiad_area_phases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.olympiad_area_phases_id_seq OWNED BY public.olympiad_area_phases.id;


--
-- Name: olympiad_areas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.olympiad_areas (
    id bigint NOT NULL,
    olympiad_id bigint NOT NULL,
    area_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.olympiad_areas OWNER TO postgres;

--
-- Name: olympiad_areas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.olympiad_areas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.olympiad_areas_id_seq OWNER TO postgres;

--
-- Name: olympiad_areas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.olympiad_areas_id_seq OWNED BY public.olympiad_areas.id;


--
-- Name: olympiads; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.olympiads (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    default_score_cut integer,
    start_date date NOT NULL,
    end_date date NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    number_of_phases integer DEFAULT 2 NOT NULL,
    status character varying(255) DEFAULT 'En planificación'::character varying NOT NULL,
    default_max_score integer,
    CONSTRAINT olympiads_status_check CHECK (((status)::text = ANY ((ARRAY['En planificación'::character varying, 'Activa'::character varying, 'Terminada'::character varying])::text[])))
);


ALTER TABLE public.olympiads OWNER TO postgres;

--
-- Name: olympiads_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.olympiads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.olympiads_id_seq OWNER TO postgres;

--
-- Name: olympiads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.olympiads_id_seq OWNED BY public.olympiads.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO postgres;

--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO postgres;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.personal_access_tokens_id_seq OWNER TO postgres;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: phases; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.phases (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    "order" integer DEFAULT 1 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.phases OWNER TO postgres;

--
-- Name: phases_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.phases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.phases_id_seq OWNER TO postgres;

--
-- Name: phases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.phases_id_seq OWNED BY public.phases.id;


--
-- Name: registrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.registrations (
    id bigint NOT NULL,
    contestant_id bigint NOT NULL,
    olympiad_area_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.registrations OWNER TO postgres;

--
-- Name: registrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.registrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.registrations_id_seq OWNER TO postgres;

--
-- Name: registrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.registrations_id_seq OWNED BY public.registrations.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    name character varying(255) NOT NULL
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.roles_id_seq OWNER TO postgres;

--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: schools; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.schools (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.schools OWNER TO postgres;

--
-- Name: schools_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.schools_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.schools_id_seq OWNER TO postgres;

--
-- Name: schools_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.schools_id_seq OWNED BY public.schools.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: tutors; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tutors (
    id bigint NOT NULL,
    first_name character varying(255) NOT NULL,
    last_name character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.tutors OWNER TO postgres;

--
-- Name: tutors_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tutors_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.tutors_id_seq OWNER TO postgres;

--
-- Name: tutors_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tutors_id_seq OWNED BY public.tutors.id;


--
-- Name: user_area_olympiads; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_area_olympiads (
    id bigint NOT NULL,
    area_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    olympiad_id bigint NOT NULL,
    user_role_id bigint NOT NULL
);


ALTER TABLE public.user_area_olympiads OWNER TO postgres;

--
-- Name: user_areas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_areas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.user_areas_id_seq OWNER TO postgres;

--
-- Name: user_areas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_areas_id_seq OWNED BY public.user_area_olympiads.id;


--
-- Name: user_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_roles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    role_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_roles OWNER TO postgres;

--
-- Name: user_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.user_roles_id_seq OWNER TO postgres;

--
-- Name: user_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_roles_id_seq OWNED BY public.user_roles.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    first_name character varying(50) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    last_name character varying(50) NOT NULL,
    ci character varying(12) NOT NULL,
    phone_number character varying(15) NOT NULL,
    genre character varying(255) NOT NULL,
    profesion character varying(100),
    CONSTRAINT users_genre_check CHECK (((genre)::text = ANY ((ARRAY['femenino'::character varying, 'masculino'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: areas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.areas ALTER COLUMN id SET DEFAULT nextval('public.areas_id_seq'::regclass);


--
-- Name: audits id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audits ALTER COLUMN id SET DEFAULT nextval('public.audits_id_seq'::regclass);


--
-- Name: contestant_level_grades id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contestant_level_grades ALTER COLUMN id SET DEFAULT nextval('public.contestant_level_grades_id_seq'::regclass);


--
-- Name: contestants id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contestants ALTER COLUMN id SET DEFAULT nextval('public.contestants_id_seq'::regclass);


--
-- Name: csv_uploads id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.csv_uploads ALTER COLUMN id SET DEFAULT nextval('public.csv_uploads_id_seq'::regclass);


--
-- Name: evaluations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations ALTER COLUMN id SET DEFAULT nextval('public.evaluations_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: grades id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.grades ALTER COLUMN id SET DEFAULT nextval('public.grades_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: level_grades id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.level_grades ALTER COLUMN id SET DEFAULT nextval('public.level_grades_id_seq'::regclass);


--
-- Name: levels id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.levels ALTER COLUMN id SET DEFAULT nextval('public.levels_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: olympiad_area_level_grades id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_level_grades ALTER COLUMN id SET DEFAULT nextval('public.olympiad_area_level_grades_id_seq'::regclass);


--
-- Name: olympiad_area_phase_level_grades id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_phase_level_grades ALTER COLUMN id SET DEFAULT nextval('public.olympiad_area_phase_level_grades_id_seq'::regclass);


--
-- Name: olympiad_area_phases id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_phases ALTER COLUMN id SET DEFAULT nextval('public.olympiad_area_phases_id_seq'::regclass);


--
-- Name: olympiad_areas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_areas ALTER COLUMN id SET DEFAULT nextval('public.olympiad_areas_id_seq'::regclass);


--
-- Name: olympiads id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiads ALTER COLUMN id SET DEFAULT nextval('public.olympiads_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: phases id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.phases ALTER COLUMN id SET DEFAULT nextval('public.phases_id_seq'::regclass);


--
-- Name: registrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.registrations ALTER COLUMN id SET DEFAULT nextval('public.registrations_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: schools id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schools ALTER COLUMN id SET DEFAULT nextval('public.schools_id_seq'::regclass);


--
-- Name: tutors id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutors ALTER COLUMN id SET DEFAULT nextval('public.tutors_id_seq'::regclass);


--
-- Name: user_area_olympiads id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_area_olympiads ALTER COLUMN id SET DEFAULT nextval('public.user_areas_id_seq'::regclass);


--
-- Name: user_roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles ALTER COLUMN id SET DEFAULT nextval('public.user_roles_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: areas areas_name_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.areas
    ADD CONSTRAINT areas_name_unique UNIQUE (name);


--
-- Name: areas areas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.areas
    ADD CONSTRAINT areas_pkey PRIMARY KEY (id);


--
-- Name: audits audits_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audits
    ADD CONSTRAINT audits_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: contestant_level_grades contestant_level_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contestant_level_grades
    ADD CONSTRAINT contestant_level_grades_pkey PRIMARY KEY (id);


--
-- Name: contestants contestants_ci_document_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contestants
    ADD CONSTRAINT contestants_ci_document_unique UNIQUE (ci_document);


--
-- Name: contestants contestants_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contestants
    ADD CONSTRAINT contestants_email_unique UNIQUE (email);


--
-- Name: contestants contestants_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contestants
    ADD CONSTRAINT contestants_pkey PRIMARY KEY (id);


--
-- Name: csv_uploads csv_uploads_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.csv_uploads
    ADD CONSTRAINT csv_uploads_pkey PRIMARY KEY (id);


--
-- Name: evaluations evaluations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations
    ADD CONSTRAINT evaluations_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: grades grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.grades
    ADD CONSTRAINT grades_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: level_grades level_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.level_grades
    ADD CONSTRAINT level_grades_pkey PRIMARY KEY (id);


--
-- Name: levels levels_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.levels
    ADD CONSTRAINT levels_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: olympiad_area_level_grades olympiad_area_level_grades_olympiad_area_id_level_grade_id_uniq; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_level_grades
    ADD CONSTRAINT olympiad_area_level_grades_olympiad_area_id_level_grade_id_uniq UNIQUE (olympiad_area_id, level_grade_id);


--
-- Name: olympiad_area_level_grades olympiad_area_level_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_level_grades
    ADD CONSTRAINT olympiad_area_level_grades_pkey PRIMARY KEY (id);


--
-- Name: olympiad_area_phase_level_grades olympiad_area_phase_level_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_phase_level_grades
    ADD CONSTRAINT olympiad_area_phase_level_grades_pkey PRIMARY KEY (id);


--
-- Name: olympiad_area_phases olympiad_area_phases_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_phases
    ADD CONSTRAINT olympiad_area_phases_pkey PRIMARY KEY (id);


--
-- Name: olympiad_areas olympiad_areas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_areas
    ADD CONSTRAINT olympiad_areas_pkey PRIMARY KEY (id);


--
-- Name: olympiads olympiads_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiads
    ADD CONSTRAINT olympiads_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: phases phases_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.phases
    ADD CONSTRAINT phases_pkey PRIMARY KEY (id);


--
-- Name: registrations registrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.registrations
    ADD CONSTRAINT registrations_pkey PRIMARY KEY (id);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: schools schools_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.schools
    ADD CONSTRAINT schools_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: tutors tutors_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tutors
    ADD CONSTRAINT tutors_pkey PRIMARY KEY (id);


--
-- Name: user_area_olympiads user_areas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_area_olympiads
    ADD CONSTRAINT user_areas_pkey PRIMARY KEY (id);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (id);


--
-- Name: users users_ci_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_ci_unique UNIQUE (ci);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: audits_auditable_type_auditable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX audits_auditable_type_auditable_id_index ON public.audits USING btree (auditable_type, auditable_id);


--
-- Name: audits_user_id_user_type_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX audits_user_id_user_type_index ON public.audits USING btree (user_id, user_type);


--
-- Name: evaluations_updated_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX evaluations_updated_at_index ON public.evaluations USING btree (updated_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: contestant_level_grades contestant_level_grades_contestant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contestant_level_grades
    ADD CONSTRAINT contestant_level_grades_contestant_id_foreign FOREIGN KEY (contestant_id) REFERENCES public.contestants(id) ON DELETE CASCADE;


--
-- Name: contestant_level_grades contestant_level_grades_level_grade_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.contestant_level_grades
    ADD CONSTRAINT contestant_level_grades_level_grade_id_foreign FOREIGN KEY (level_grade_id) REFERENCES public.level_grades(id) ON DELETE CASCADE;


--
-- Name: csv_uploads csv_uploads_olympiad_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.csv_uploads
    ADD CONSTRAINT csv_uploads_olympiad_id_foreign FOREIGN KEY (olympiad_id) REFERENCES public.olympiads(id) ON DELETE CASCADE;


--
-- Name: evaluations evaluations_olympiad_area_phase_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations
    ADD CONSTRAINT evaluations_olympiad_area_phase_id_foreign FOREIGN KEY (olympiad_area_phase_id) REFERENCES public.olympiad_area_phases(id) ON DELETE CASCADE;


--
-- Name: evaluations evaluations_registration_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.evaluations
    ADD CONSTRAINT evaluations_registration_id_foreign FOREIGN KEY (registration_id) REFERENCES public.registrations(id) ON DELETE CASCADE;


--
-- Name: level_grades level_grades_grade_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.level_grades
    ADD CONSTRAINT level_grades_grade_id_foreign FOREIGN KEY (grade_id) REFERENCES public.grades(id) ON DELETE CASCADE;


--
-- Name: level_grades level_grades_level_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.level_grades
    ADD CONSTRAINT level_grades_level_id_foreign FOREIGN KEY (level_id) REFERENCES public.levels(id) ON DELETE CASCADE;


--
-- Name: level_grades level_grades_olympiad_area_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.level_grades
    ADD CONSTRAINT level_grades_olympiad_area_id_foreign FOREIGN KEY (olympiad_area_id) REFERENCES public.olympiad_areas(id) ON DELETE CASCADE;


--
-- Name: olympiad_area_level_grades olympiad_area_level_grades_level_grade_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_level_grades
    ADD CONSTRAINT olympiad_area_level_grades_level_grade_id_foreign FOREIGN KEY (level_grade_id) REFERENCES public.level_grades(id) ON DELETE CASCADE;


--
-- Name: olympiad_area_level_grades olympiad_area_level_grades_olympiad_area_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_level_grades
    ADD CONSTRAINT olympiad_area_level_grades_olympiad_area_id_foreign FOREIGN KEY (olympiad_area_id) REFERENCES public.olympiad_areas(id) ON DELETE CASCADE;


--
-- Name: olympiad_area_phase_level_grades olympiad_area_phase_level_grades_level_grade_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_phase_level_grades
    ADD CONSTRAINT olympiad_area_phase_level_grades_level_grade_id_foreign FOREIGN KEY (level_grade_id) REFERENCES public.level_grades(id) ON DELETE CASCADE;


--
-- Name: olympiad_area_phase_level_grades olympiad_area_phase_level_grades_olympiad_area_phase_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_phase_level_grades
    ADD CONSTRAINT olympiad_area_phase_level_grades_olympiad_area_phase_id_foreign FOREIGN KEY (olympiad_area_phase_id) REFERENCES public.olympiad_area_phases(id) ON DELETE CASCADE;


--
-- Name: olympiad_area_phases olympiad_area_phases_olympiad_area_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_phases
    ADD CONSTRAINT olympiad_area_phases_olympiad_area_id_foreign FOREIGN KEY (olympiad_area_id) REFERENCES public.olympiad_areas(id) ON DELETE CASCADE;


--
-- Name: olympiad_area_phases olympiad_area_phases_phase_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_area_phases
    ADD CONSTRAINT olympiad_area_phases_phase_id_foreign FOREIGN KEY (phase_id) REFERENCES public.phases(id) ON DELETE CASCADE;


--
-- Name: olympiad_areas olympiad_areas_area_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_areas
    ADD CONSTRAINT olympiad_areas_area_id_foreign FOREIGN KEY (area_id) REFERENCES public.areas(id) ON DELETE CASCADE;


--
-- Name: olympiad_areas olympiad_areas_olympiad_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.olympiad_areas
    ADD CONSTRAINT olympiad_areas_olympiad_id_foreign FOREIGN KEY (olympiad_id) REFERENCES public.olympiads(id) ON DELETE CASCADE;


--
-- Name: registrations registrations_contestant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.registrations
    ADD CONSTRAINT registrations_contestant_id_foreign FOREIGN KEY (contestant_id) REFERENCES public.contestants(id) ON DELETE CASCADE;


--
-- Name: registrations registrations_olympiad_area_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.registrations
    ADD CONSTRAINT registrations_olympiad_area_id_foreign FOREIGN KEY (olympiad_area_id) REFERENCES public.olympiad_areas(id) ON DELETE CASCADE;


--
-- Name: user_area_olympiads user_area_olympiads_user_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_area_olympiads
    ADD CONSTRAINT user_area_olympiads_user_role_id_foreign FOREIGN KEY (user_role_id) REFERENCES public.user_roles(id) ON DELETE CASCADE;


--
-- Name: user_area_olympiads user_areas_area_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_area_olympiads
    ADD CONSTRAINT user_areas_area_id_foreign FOREIGN KEY (area_id) REFERENCES public.areas(id) ON DELETE CASCADE;


--
-- Name: user_area_olympiads user_areas_olympiad_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_area_olympiads
    ADD CONSTRAINT user_areas_olympiad_id_foreign FOREIGN KEY (olympiad_id) REFERENCES public.olympiads(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

