--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.0

-- Started on 2025-11-30 18:05:54 CET

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 3 (class 3079 OID 26348)
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public;


--
-- TOC entry 3747 (class 0 OID 0)
-- Dependencies: 3
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


--
-- TOC entry 4 (class 3079 OID 26454)
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- TOC entry 3748 (class 0 OID 0)
-- Dependencies: 4
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- TOC entry 2 (class 3079 OID 26341)
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- TOC entry 3749 (class 0 OID 0)
-- Dependencies: 2
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 226 (class 1259 OID 26321)
-- Name: chat_messages; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.chat_messages (
    message_id bigint NOT NULL,
    session_id uuid NOT NULL,
    role character varying(50) NOT NULL,
    text character varying(5000) NOT NULL,
    created_at_utc timestamp with time zone DEFAULT now() NOT NULL,
    origin_fact_id character varying(255),
    CONSTRAINT chat_messages_role_check CHECK (((role)::text = ANY ((ARRAY['user'::character varying, 'bot'::character varying, 'system'::character varying])::text[])))
);


ALTER TABLE public.chat_messages OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 26320)
-- Name: chat_messages_message_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.chat_messages_message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.chat_messages_message_id_seq OWNER TO postgres;

--
-- TOC entry 3750 (class 0 OID 0)
-- Dependencies: 225
-- Name: chat_messages_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.chat_messages_message_id_seq OWNED BY public.chat_messages.message_id;


--
-- TOC entry 224 (class 1259 OID 26312)
-- Name: chat_sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.chat_sessions (
    session_id uuid DEFAULT gen_random_uuid() NOT NULL,
    started_at_utc timestamp with time zone DEFAULT now() NOT NULL,
    last_active_utc timestamp with time zone DEFAULT now() NOT NULL,
    client_label character varying(255),
    user_id integer
);


ALTER TABLE public.chat_sessions OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 26297)
-- Name: fact_tag_links; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fact_tag_links (
    fact_id character varying(255) NOT NULL,
    tag_id integer NOT NULL
);


ALTER TABLE public.fact_tag_links OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 26287)
-- Name: fact_tags; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.fact_tags (
    tag_id integer NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(1000)
);


ALTER TABLE public.fact_tags OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 26286)
-- Name: fact_tags_tag_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.fact_tags_tag_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.fact_tags_tag_id_seq OWNER TO postgres;

--
-- TOC entry 3751 (class 0 OID 0)
-- Dependencies: 221
-- Name: fact_tags_tag_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.fact_tags_tag_id_seq OWNED BY public.fact_tags.tag_id;


--
-- TOC entry 220 (class 1259 OID 26277)
-- Name: facts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.facts (
    fact_id character varying(255) NOT NULL,
    title character varying(255),
    text character varying(5000) NOT NULL,
    source character varying(255),
    language character varying(10) DEFAULT 'no'::character varying NOT NULL,
    created_at_utc timestamp with time zone DEFAULT now() NOT NULL,
    last_verified_on date,
    search_vector tsvector GENERATED ALWAYS AS (to_tsvector('norwegian'::regconfig, (COALESCE(text, ''::character varying))::text)) STORED
);


ALTER TABLE public.facts OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 26438)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id_user integer NOT NULL,
    email character varying(255) NOT NULL,
    first_name character varying(100),
    last_name character varying(100),
    password_hash character varying(255) NOT NULL
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 26437)
-- Name: users_id_user_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_user_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_user_seq OWNER TO postgres;

--
-- TOC entry 3752 (class 0 OID 0)
-- Dependencies: 227
-- Name: users_id_user_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_user_seq OWNED BY public.users.id_user;


--
-- TOC entry 3571 (class 2604 OID 26324)
-- Name: chat_messages message_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chat_messages ALTER COLUMN message_id SET DEFAULT nextval('public.chat_messages_message_id_seq'::regclass);


--
-- TOC entry 3567 (class 2604 OID 26290)
-- Name: fact_tags tag_id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fact_tags ALTER COLUMN tag_id SET DEFAULT nextval('public.fact_tags_tag_id_seq'::regclass);


--
-- TOC entry 3573 (class 2604 OID 26441)
-- Name: users id_user; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id_user SET DEFAULT nextval('public.users_id_user_seq'::regclass);


--
-- TOC entry 3587 (class 2606 OID 26330)
-- Name: chat_messages chat_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chat_messages
    ADD CONSTRAINT chat_messages_pkey PRIMARY KEY (message_id);


--
-- TOC entry 3585 (class 2606 OID 26319)
-- Name: chat_sessions chat_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chat_sessions
    ADD CONSTRAINT chat_sessions_pkey PRIMARY KEY (session_id);


--
-- TOC entry 3583 (class 2606 OID 26301)
-- Name: fact_tag_links fact_tag_links_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fact_tag_links
    ADD CONSTRAINT fact_tag_links_pkey PRIMARY KEY (fact_id, tag_id);


--
-- TOC entry 3579 (class 2606 OID 26296)
-- Name: fact_tags fact_tags_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fact_tags
    ADD CONSTRAINT fact_tags_name_key UNIQUE (name);


--
-- TOC entry 3581 (class 2606 OID 26294)
-- Name: fact_tags fact_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fact_tags
    ADD CONSTRAINT fact_tags_pkey PRIMARY KEY (tag_id);


--
-- TOC entry 3576 (class 2606 OID 26285)
-- Name: facts facts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.facts
    ADD CONSTRAINT facts_pkey PRIMARY KEY (fact_id);


--
-- TOC entry 3589 (class 2606 OID 26447)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 3591 (class 2606 OID 26445)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id_user);


--
-- TOC entry 3577 (class 1259 OID 26436)
-- Name: idx_facts_search_vector_gin; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_facts_search_vector_gin ON public.facts USING gin (search_vector);


--
-- TOC entry 3595 (class 2606 OID 26336)
-- Name: chat_messages chat_messages_origin_fact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chat_messages
    ADD CONSTRAINT chat_messages_origin_fact_id_fkey FOREIGN KEY (origin_fact_id) REFERENCES public.facts(fact_id);


--
-- TOC entry 3596 (class 2606 OID 26331)
-- Name: chat_messages chat_messages_session_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chat_messages
    ADD CONSTRAINT chat_messages_session_id_fkey FOREIGN KEY (session_id) REFERENCES public.chat_sessions(session_id) ON DELETE CASCADE;


--
-- TOC entry 3594 (class 2606 OID 26449)
-- Name: chat_sessions chat_sessions_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.chat_sessions
    ADD CONSTRAINT chat_sessions_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id_user);


--
-- TOC entry 3592 (class 2606 OID 26302)
-- Name: fact_tag_links fact_tag_links_fact_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fact_tag_links
    ADD CONSTRAINT fact_tag_links_fact_id_fkey FOREIGN KEY (fact_id) REFERENCES public.facts(fact_id) ON DELETE CASCADE;


--
-- TOC entry 3593 (class 2606 OID 26307)
-- Name: fact_tag_links fact_tag_links_tag_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.fact_tag_links
    ADD CONSTRAINT fact_tag_links_tag_id_fkey FOREIGN KEY (tag_id) REFERENCES public.fact_tags(tag_id) ON DELETE CASCADE;


-- Completed on 2025-11-30 18:05:54 CET

--
-- PostgreSQL database dump complete
--

