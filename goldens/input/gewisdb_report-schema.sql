--
-- PostgreSQL database dump
--


-- Dumped from database version 16.15
-- Dumped by pg_dump version 16.15

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
-- Name: address; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.address (
    type character varying(255) NOT NULL,
    lidnr integer NOT NULL,
    country character varying(255) NOT NULL,
    street character varying(255) NOT NULL,
    number character varying(255) NOT NULL,
    postalcode character varying(255) NOT NULL,
    city character varying(255) NOT NULL,
    phone character varying(255) NOT NULL
);


--
-- Name: boardmember; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.boardmember (
    id integer NOT NULL,
    lidnr integer NOT NULL,
    r_meeting_type character varying(255) DEFAULT NULL::character varying,
    r_meeting_number integer,
    r_decision_point integer,
    r_decision_number integer,
    r_sequence integer,
    function character varying(255) NOT NULL,
    installdate date NOT NULL,
    releasedate date,
    dischargedate date
);


--
-- Name: boardmember_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.boardmember_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: decision; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.decision (
    meeting_type character varying(255) NOT NULL,
    meeting_number integer NOT NULL,
    point integer NOT NULL,
    number integer NOT NULL,
    contentnl text NOT NULL,
    contenten text NOT NULL
);


--
-- Name: doctrine_migration_versions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.doctrine_migration_versions (
    version character varying(191) NOT NULL,
    executed_at timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    execution_time integer
);


--
-- Name: keyholder; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.keyholder (
    id integer NOT NULL,
    lidnr integer NOT NULL,
    r_meeting_type character varying(255) DEFAULT NULL::character varying,
    r_meeting_number integer,
    r_decision_point integer,
    r_decision_number integer,
    r_sequence integer,
    expirationdate date NOT NULL,
    withdrawndate date
);


--
-- Name: keyholder_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.keyholder_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: mailinglist; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mailinglist (
    name character varying(255) NOT NULL,
    nl_description text NOT NULL,
    en_description text NOT NULL
);


--
-- Name: mailinglistmember; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mailinglistmember (
    email character varying(255) NOT NULL,
    member integer NOT NULL,
    mailinglist character varying(255) NOT NULL
);


--
-- Name: meeting; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.meeting (
    type character varying(255) NOT NULL,
    number integer NOT NULL,
    date date NOT NULL
);


--
-- Name: member; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.member (
    lidnr integer NOT NULL,
    email character varying(255) DEFAULT NULL::character varying,
    lastname character varying(255) NOT NULL,
    middlename character varying(255) NOT NULL,
    initials character varying(255) NOT NULL,
    firstname character varying(255) NOT NULL,
    generation integer NOT NULL,
    type character varying(255) NOT NULL,
    changedon date NOT NULL,
    membershipendson date,
    birth date NOT NULL,
    expiration date NOT NULL,
    supremum character varying(255) DEFAULT NULL::character varying,
    hidden boolean DEFAULT false NOT NULL,
    authenticationkey character varying(255) DEFAULT NULL::character varying,
    deleted boolean DEFAULT false NOT NULL
);


--
-- Name: organ; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.organ (
    id integer NOT NULL,
    r_meeting_type character varying(255) DEFAULT NULL::character varying,
    r_meeting_number integer,
    r_decision_point integer,
    r_decision_number integer,
    r_sequence integer,
    abbr character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    foundationdate date NOT NULL,
    abrogationdate date
);


--
-- Name: organ_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.organ_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: organmember; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.organmember (
    id integer NOT NULL,
    organ_id integer,
    lidnr integer,
    r_meeting_type character varying(255) DEFAULT NULL::character varying,
    r_meeting_number integer,
    r_decision_point integer,
    r_decision_number integer,
    r_sequence integer,
    function character varying(255) NOT NULL,
    installdate date NOT NULL,
    dischargedate date
);


--
-- Name: organmember_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.organmember_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: organs_subdecisions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.organs_subdecisions (
    organ_id integer NOT NULL,
    meeting_type character varying(255) NOT NULL,
    meeting_number integer NOT NULL,
    decision_point integer NOT NULL,
    decision_number integer NOT NULL,
    subdecision_sequence integer NOT NULL
);


--
-- Name: subdecision; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.subdecision (
    meeting_type character varying(255) NOT NULL,
    meeting_number integer NOT NULL,
    decision_point integer NOT NULL,
    decision_number integer NOT NULL,
    sequence integer NOT NULL,
    lidnr integer,
    r_meeting_type character varying(255) DEFAULT NULL::character varying,
    r_meeting_number integer,
    r_decision_point integer,
    r_decision_number integer,
    r_sequence integer,
    contentnl text NOT NULL,
    type character varying(255) NOT NULL,
    name character varying(255) DEFAULT NULL::character varying,
    organtype character varying(255) DEFAULT NULL::character varying,
    version character varying(32) DEFAULT NULL::character varying,
    date date,
    approval boolean,
    changes boolean,
    abbr character varying(255) DEFAULT NULL::character varying,
    function character varying(255) DEFAULT NULL::character varying,
    until date,
    withdrawnon date,
    contenten text NOT NULL,
    purpose character varying(255) DEFAULT NULL::character varying
);


--
-- Name: address address_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.address
    ADD CONSTRAINT address_pkey PRIMARY KEY (lidnr, type);


--
-- Name: boardmember boardmember_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.boardmember
    ADD CONSTRAINT boardmember_pkey PRIMARY KEY (id);


--
-- Name: decision decision_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decision
    ADD CONSTRAINT decision_pkey PRIMARY KEY (meeting_type, meeting_number, point, number);


--
-- Name: doctrine_migration_versions doctrine_migration_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.doctrine_migration_versions
    ADD CONSTRAINT doctrine_migration_versions_pkey PRIMARY KEY (version);


--
-- Name: keyholder keyholder_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.keyholder
    ADD CONSTRAINT keyholder_pkey PRIMARY KEY (id);


--
-- Name: mailinglist mailinglist_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mailinglist
    ADD CONSTRAINT mailinglist_pkey PRIMARY KEY (name);


--
-- Name: mailinglistmember mailinglistmember_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mailinglistmember
    ADD CONSTRAINT mailinglistmember_pkey PRIMARY KEY (mailinglist, email);


--
-- Name: meeting meeting_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.meeting
    ADD CONSTRAINT meeting_pkey PRIMARY KEY (type, number);


--
-- Name: member member_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.member
    ADD CONSTRAINT member_pkey PRIMARY KEY (lidnr);


--
-- Name: organ organ_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organ
    ADD CONSTRAINT organ_pkey PRIMARY KEY (id);


--
-- Name: organmember organmember_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organmember
    ADD CONSTRAINT organmember_pkey PRIMARY KEY (id);


--
-- Name: organs_subdecisions organs_subdecisions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organs_subdecisions
    ADD CONSTRAINT organs_subdecisions_pkey PRIMARY KEY (organ_id, meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence);


--
-- Name: subdecision subdecision_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subdecision
    ADD CONSTRAINT subdecision_pkey PRIMARY KEY (meeting_type, meeting_number, decision_point, decision_number, sequence);


--
-- Name: foundation_uniq; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX foundation_uniq ON public.organ USING btree (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence);


--
-- Name: grantingdec_uniq; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX grantingdec_uniq ON public.keyholder USING btree (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence);


--
-- Name: idx_3a8467a970e4fa78; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_3a8467a970e4fa78 ON public.mailinglistmember USING btree (member);


--
-- Name: idx_3a8467a97b1ac3ed; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_3a8467a97b1ac3ed ON public.mailinglistmember USING btree (mailinglist);


--
-- Name: idx_3c5f7b4dd665e01d; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_3c5f7b4dd665e01d ON public.keyholder USING btree (lidnr);


--
-- Name: idx_6177e308602faffb96f82e1690e0342def6be237dd50eb88; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_6177e308602faffb96f82e1690e0342def6be237dd50eb88 ON public.organs_subdecisions USING btree (meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence);


--
-- Name: idx_6177e308e4445171; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_6177e308e4445171 ON public.organs_subdecisions USING btree (organ_id);


--
-- Name: idx_7ddadc1e602faffb96f82e16; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_7ddadc1e602faffb96f82e16 ON public.decision USING btree (meeting_type, meeting_number);


--
-- Name: idx_c2f3561dd665e01d; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_c2f3561dd665e01d ON public.address USING btree (lidnr);


--
-- Name: idx_d9517b2ed665e01d; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_d9517b2ed665e01d ON public.boardmember USING btree (lidnr);


--
-- Name: idx_e5cb2c7dd665e01d; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_e5cb2c7dd665e01d ON public.organmember USING btree (lidnr);


--
-- Name: idx_e5cb2c7de4445171; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_e5cb2c7de4445171 ON public.organmember USING btree (organ_id);


--
-- Name: idx_f0d6ee40602faffb96f82e1690e0342def6be237; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_f0d6ee40602faffb96f82e1690e0342def6be237 ON public.subdecision USING btree (meeting_type, meeting_number, decision_point, decision_number);


--
-- Name: idx_f0d6ee40d665e01d; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_f0d6ee40d665e01d ON public.subdecision USING btree (lidnr);


--
-- Name: idx_f0d6ee40efba85ff292fad51; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_f0d6ee40efba85ff292fad51 ON public.subdecision USING btree (r_meeting_type, r_meeting_number);


--
-- Name: idx_f0d6ee40efba85ff292fad512f37b76a76ce187; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_f0d6ee40efba85ff292fad512f37b76a76ce187 ON public.subdecision USING btree (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number);


--
-- Name: idx_f0d6ee40efba85ff292fad512f37b76a76ce1878b79bb36; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_f0d6ee40efba85ff292fad512f37b76a76ce1878b79bb36 ON public.subdecision USING btree (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence);


--
-- Name: installation_uniq; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX installation_uniq ON public.organmember USING btree (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence);


--
-- Name: installationdec_uniq; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX installationdec_uniq ON public.boardmember USING btree (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence);


--
-- Name: mailinglistmember_unique_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX mailinglistmember_unique_idx ON public.mailinglistmember USING btree (mailinglist, member, email);


--
-- Name: mailinglistmember fk_3a8467a970e4fa78; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mailinglistmember
    ADD CONSTRAINT fk_3a8467a970e4fa78 FOREIGN KEY (member) REFERENCES public.member(lidnr);


--
-- Name: mailinglistmember fk_3a8467a97b1ac3ed; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mailinglistmember
    ADD CONSTRAINT fk_3a8467a97b1ac3ed FOREIGN KEY (mailinglist) REFERENCES public.mailinglist(name);


--
-- Name: keyholder fk_3c5f7b4dd665e01d; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.keyholder
    ADD CONSTRAINT fk_3c5f7b4dd665e01d FOREIGN KEY (lidnr) REFERENCES public.member(lidnr);


--
-- Name: keyholder fk_3c5f7b4defba85ff292fad512f37b76a76ce1878b79bb36; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.keyholder
    ADD CONSTRAINT fk_3c5f7b4defba85ff292fad512f37b76a76ce1878b79bb36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES public.subdecision(meeting_type, meeting_number, decision_point, decision_number, sequence);


--
-- Name: organ fk_46c39b8eefba85ff292fad512f37b76a76ce1878b79bb36; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organ
    ADD CONSTRAINT fk_46c39b8eefba85ff292fad512f37b76a76ce1878b79bb36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES public.subdecision(meeting_type, meeting_number, decision_point, decision_number, sequence);


--
-- Name: organs_subdecisions fk_6177e308602faffb96f82e1690e0342def6be237dd50eb88; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organs_subdecisions
    ADD CONSTRAINT fk_6177e308602faffb96f82e1690e0342def6be237dd50eb88 FOREIGN KEY (meeting_type, meeting_number, decision_point, decision_number, subdecision_sequence) REFERENCES public.subdecision(meeting_type, meeting_number, decision_point, decision_number, sequence);


--
-- Name: organs_subdecisions fk_6177e308e4445171; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organs_subdecisions
    ADD CONSTRAINT fk_6177e308e4445171 FOREIGN KEY (organ_id) REFERENCES public.organ(id);


--
-- Name: decision fk_7ddadc1e602faffb96f82e16; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decision
    ADD CONSTRAINT fk_7ddadc1e602faffb96f82e16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES public.meeting(type, number);


--
-- Name: address fk_c2f3561dd665e01d; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.address
    ADD CONSTRAINT fk_c2f3561dd665e01d FOREIGN KEY (lidnr) REFERENCES public.member(lidnr);


--
-- Name: boardmember fk_d9517b2ed665e01d; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.boardmember
    ADD CONSTRAINT fk_d9517b2ed665e01d FOREIGN KEY (lidnr) REFERENCES public.member(lidnr);


--
-- Name: boardmember fk_d9517b2eefba85ff292fad512f37b76a76ce1878b79bb36; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.boardmember
    ADD CONSTRAINT fk_d9517b2eefba85ff292fad512f37b76a76ce1878b79bb36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES public.subdecision(meeting_type, meeting_number, decision_point, decision_number, sequence);


--
-- Name: organmember fk_e5cb2c7dd665e01d; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organmember
    ADD CONSTRAINT fk_e5cb2c7dd665e01d FOREIGN KEY (lidnr) REFERENCES public.member(lidnr);


--
-- Name: organmember fk_e5cb2c7de4445171; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organmember
    ADD CONSTRAINT fk_e5cb2c7de4445171 FOREIGN KEY (organ_id) REFERENCES public.organ(id);


--
-- Name: organmember fk_e5cb2c7defba85ff292fad512f37b76a76ce1878b79bb36; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.organmember
    ADD CONSTRAINT fk_e5cb2c7defba85ff292fad512f37b76a76ce1878b79bb36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES public.subdecision(meeting_type, meeting_number, decision_point, decision_number, sequence);


--
-- Name: subdecision fk_f0d6ee40602faffb96f82e1690e0342def6be237; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subdecision
    ADD CONSTRAINT fk_f0d6ee40602faffb96f82e1690e0342def6be237 FOREIGN KEY (meeting_type, meeting_number, decision_point, decision_number) REFERENCES public.decision(meeting_type, meeting_number, point, number);


--
-- Name: subdecision fk_f0d6ee40d665e01d; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subdecision
    ADD CONSTRAINT fk_f0d6ee40d665e01d FOREIGN KEY (lidnr) REFERENCES public.member(lidnr);


--
-- Name: subdecision fk_f0d6ee40efba85ff292fad51; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subdecision
    ADD CONSTRAINT fk_f0d6ee40efba85ff292fad51 FOREIGN KEY (r_meeting_type, r_meeting_number) REFERENCES public.meeting(type, number);


--
-- Name: subdecision fk_f0d6ee40efba85ff292fad512f37b76a76ce187; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subdecision
    ADD CONSTRAINT fk_f0d6ee40efba85ff292fad512f37b76a76ce187 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number) REFERENCES public.decision(meeting_type, meeting_number, point, number);


--
-- Name: subdecision fk_f0d6ee40efba85ff292fad512f37b76a76ce1878b79bb36; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subdecision
    ADD CONSTRAINT fk_f0d6ee40efba85ff292fad512f37b76a76ce1878b79bb36 FOREIGN KEY (r_meeting_type, r_meeting_number, r_decision_point, r_decision_number, r_sequence) REFERENCES public.subdecision(meeting_type, meeting_number, decision_point, decision_number, sequence);


--
-- PostgreSQL database dump complete
--


