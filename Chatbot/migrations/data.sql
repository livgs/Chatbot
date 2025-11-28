--
-- PostgreSQL database dump
--

-- Dumped from database version 17.4
-- Dumped by pg_dump version 17.0

-- Started on 2025-11-28 11:18:29 CET

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
-- TOC entry 3697 (class 0 OID 26312)
-- Dependencies: 223
-- Data for Name: chat_sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.chat_sessions (session_id, started_at_utc, last_active_utc, client_label) FROM stdin;
\.


--
-- TOC entry 3693 (class 0 OID 26277)
-- Dependencies: 219
-- Data for Name: facts; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.facts (fact_id, title, text, source, language, created_at_utc, last_verified_on) FROM stdin;
solsystemet_solen_sentrum	Solens posisjon i solsystemet	Solen utgjør sentrum av solsystemet.	Store norske leksikon – solsystemet	no	2025-11-14 10:41:06.651862+01	2025-11-14
\.


--
-- TOC entry 3699 (class 0 OID 26321)
-- Dependencies: 225
-- Data for Name: chat_messages; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.chat_messages (message_id, session_id, role, text, created_at_utc, origin_fact_id) FROM stdin;
\.


--
-- TOC entry 3695 (class 0 OID 26287)
-- Dependencies: 221
-- Data for Name: fact_tags; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fact_tags (tag_id, name, description) FROM stdin;
\.


--
-- TOC entry 3696 (class 0 OID 26297)
-- Dependencies: 222
-- Data for Name: fact_tag_links; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.fact_tag_links (fact_id, tag_id) FROM stdin;
\.


--
-- TOC entry 3701 (class 0 OID 26438)
-- Dependencies: 227
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id_user, email, first_name, last_name, password_hash) FROM stdin;
\.


--
-- TOC entry 3707 (class 0 OID 0)
-- Dependencies: 224
-- Name: chat_messages_message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.chat_messages_message_id_seq', 1, false);


--
-- TOC entry 3708 (class 0 OID 0)
-- Dependencies: 220
-- Name: fact_tags_tag_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.fact_tags_tag_id_seq', 1, false);


--
-- TOC entry 3709 (class 0 OID 0)
-- Dependencies: 226
-- Name: users_id_user_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_user_seq', 1, false);


-- Completed on 2025-11-28 11:18:29 CET

--
-- PostgreSQL database dump complete
--

