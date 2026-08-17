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

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

-- *not* creating schema, since initdb creates it


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: actionlink; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.actionlink (
    id integer NOT NULL,
    prospective_member integer,
    member integer,
    used boolean NOT NULL,
    token character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    currentexpiration date,
    newexpiration date
);


--
-- Name: actionlink_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.actionlink_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


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
-- Name: apiprincipal; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.apiprincipal (
    id integer NOT NULL,
    token character varying(255) NOT NULL,
    description character varying(255) DEFAULT NULL::character varying,
    permissions text,
    createdat timestamp(0) without time zone NOT NULL,
    updatedat timestamp(0) without time zone NOT NULL
);


--
-- Name: apiprincipal_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.apiprincipal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auditentry; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auditentry (
    id integer NOT NULL,
    user_id integer,
    member integer,
    createdat timestamp(0) without time zone NOT NULL,
    updatedat timestamp(0) without time zone NOT NULL,
    type character varying(255) NOT NULL,
    note character varying(255) DEFAULT NULL::character varying,
    oldexpiration timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    newexpiration timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    action character varying(255) DEFAULT NULL::character varying,
    mailing_list character varying(255) DEFAULT NULL::character varying,
    email character varying(255) DEFAULT NULL::character varying,
    origin character varying(255) DEFAULT NULL::character varying
);


--
-- Name: auditentry_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auditentry_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: checkoutsession; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.checkoutsession (
    id integer NOT NULL,
    prospective_member integer,
    recovered_from_id integer,
    checkoutid character varying(255) NOT NULL,
    created timestamp(0) without time zone NOT NULL,
    expiration timestamp(0) without time zone NOT NULL,
    paymentintentid character varying(255) DEFAULT NULL::character varying,
    recoveryurl character varying(255) DEFAULT NULL::character varying,
    state integer NOT NULL
);


--
-- Name: checkoutsession_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.checkoutsession_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: configitem; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.configitem (
    id integer NOT NULL,
    namespace character varying(255) NOT NULL,
    key character varying(255) NOT NULL,
    valuestring character varying(255) DEFAULT NULL::character varying,
    valuedate timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    createdat timestamp(0) without time zone NOT NULL,
    updatedat timestamp(0) without time zone NOT NULL,
    valuebool boolean,
    version integer DEFAULT 1000 NOT NULL
);


--
-- Name: configitem_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.configitem_id_seq
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
    number integer NOT NULL
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
-- Name: listmonkmailinglist; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.listmonkmailinglist (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    lastseen timestamp(0) without time zone NOT NULL,
    lastcheck timestamp(0) without time zone DEFAULT NULL::timestamp without time zone
);


--
-- Name: mailinglist; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mailinglist (
    name character varying(255) NOT NULL,
    nl_description text NOT NULL,
    en_description text NOT NULL,
    onform boolean NOT NULL,
    defaultsub boolean NOT NULL,
    mailmanid character varying(255) DEFAULT NULL::character varying,
    listmonkid integer
);


--
-- Name: mailinglistmember; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mailinglistmember (
    email character varying(255) NOT NULL,
    member integer,
    lastsyncon timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    lastsyncsuccess boolean NOT NULL,
    tobecreated boolean NOT NULL,
    tobedeleted boolean NOT NULL,
    mailinglist character varying(255) NOT NULL
);


--
-- Name: mailmanmailinglist; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.mailmanmailinglist (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    lastseen timestamp(0) without time zone NOT NULL,
    lastcheck timestamp(0) without time zone DEFAULT NULL::timestamp without time zone
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
    studentnumber character varying(255) DEFAULT NULL::character varying,
    study character varying(255) DEFAULT NULL::character varying NOT NULL,
    changedon date NOT NULL,
    lastcheckedon date,
    birth date NOT NULL,
    supremum character varying(255) DEFAULT NULL::character varying,
    hidden boolean DEFAULT false NOT NULL,
    authenticationkey character varying(255) DEFAULT NULL::character varying,
    deleted boolean DEFAULT false NOT NULL
);


--
-- Name: member_lidnr_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.member_lidnr_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: membership; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.membership (
    member_lidnr integer NOT NULL,
    startdate timestamp(0) without time zone NOT NULL,
    enddate date NOT NULL,
    paid integer NOT NULL,
    type character varying(255) NOT NULL
);


--
-- Name: memberupdate; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.memberupdate (
    lidnr integer NOT NULL,
    requesteddate date NOT NULL,
    email character varying(255) NOT NULL,
    lastname character varying(255) NOT NULL,
    middlename character varying(255) NOT NULL,
    initials character varying(255) NOT NULL,
    firstname character varying(255) NOT NULL
);


--
-- Name: prospectivemember; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.prospectivemember (
    lidnr integer NOT NULL,
    email character varying(255) NOT NULL,
    lastname character varying(255) NOT NULL,
    middlename character varying(255) NOT NULL,
    initials character varying(255) NOT NULL,
    firstname character varying(255) NOT NULL,
    studentnumber character varying(255) DEFAULT NULL::character varying,
    study character varying(255) DEFAULT NULL::character varying NOT NULL,
    changedon date NOT NULL,
    birth date NOT NULL,
    paid integer NOT NULL,
    country character varying(255) NOT NULL,
    street character varying(255) NOT NULL,
    number character varying(255) NOT NULL,
    postalcode character varying(255) NOT NULL,
    city character varying(255) NOT NULL,
    phone character varying(255) NOT NULL,
    lists text
);


--
-- Name: prospectivemember_lidnr_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.prospectivemember_lidnr_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: savedquery; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.savedquery (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    query text NOT NULL,
    category character varying(255) NOT NULL
);


--
-- Name: savedquery_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.savedquery_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


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
    type character varying(255) NOT NULL,
    name character varying(255) DEFAULT NULL::character varying,
    organtype character varying(255) DEFAULT NULL::character varying,
    version character varying(32) DEFAULT NULL::character varying,
    date date,
    approval boolean,
    changes boolean,
    abbr character varying(255) DEFAULT NULL::character varying,
    function character varying(255) DEFAULT NULL::character varying,
    content text,
    until date,
    withdrawnon date,
    purpose character varying(255) DEFAULT NULL::character varying
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id integer NOT NULL,
    login character varying(255) NOT NULL,
    password character varying(255) DEFAULT NULL::character varying,
    createdat timestamp(0) without time zone NOT NULL,
    updatedat timestamp(0) without time zone NOT NULL
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: actionlink actionlink_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actionlink
    ADD CONSTRAINT actionlink_pkey PRIMARY KEY (id);


--
-- Name: address address_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.address
    ADD CONSTRAINT address_pkey PRIMARY KEY (lidnr, type);


--
-- Name: apiprincipal apiprincipal_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.apiprincipal
    ADD CONSTRAINT apiprincipal_pkey PRIMARY KEY (id);


--
-- Name: auditentry auditentry_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditentry
    ADD CONSTRAINT auditentry_pkey PRIMARY KEY (id);


--
-- Name: checkoutsession checkoutsession_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkoutsession
    ADD CONSTRAINT checkoutsession_pkey PRIMARY KEY (id);


--
-- Name: configitem configitem_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.configitem
    ADD CONSTRAINT configitem_pkey PRIMARY KEY (id);


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
-- Name: listmonkmailinglist listmonkmailinglist_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.listmonkmailinglist
    ADD CONSTRAINT listmonkmailinglist_pkey PRIMARY KEY (id);


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
-- Name: mailmanmailinglist mailmanmailinglist_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mailmanmailinglist
    ADD CONSTRAINT mailmanmailinglist_pkey PRIMARY KEY (id);


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
-- Name: membership membership_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.membership
    ADD CONSTRAINT membership_pkey PRIMARY KEY (member_lidnr, startdate);


--
-- Name: memberupdate memberupdate_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memberupdate
    ADD CONSTRAINT memberupdate_pkey PRIMARY KEY (lidnr);


--
-- Name: prospectivemember prospectivemember_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.prospectivemember
    ADD CONSTRAINT prospectivemember_pkey PRIMARY KEY (lidnr);


--
-- Name: savedquery savedquery_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.savedquery
    ADD CONSTRAINT savedquery_pkey PRIMARY KEY (id);


--
-- Name: subdecision subdecision_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.subdecision
    ADD CONSTRAINT subdecision_pkey PRIMARY KEY (meeting_type, meeting_number, decision_point, decision_number, sequence);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: configitem_unique_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX configitem_unique_idx ON public.configitem USING btree (namespace, key);


--
-- Name: idx_3a8467a970e4fa78; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_3a8467a970e4fa78 ON public.mailinglistmember USING btree (member);


--
-- Name: idx_3a8467a97b1ac3ed; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_3a8467a97b1ac3ed ON public.mailinglistmember USING btree (mailinglist);


--
-- Name: idx_7ddadc1e602faffb96f82e16; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_7ddadc1e602faffb96f82e16 ON public.decision USING btree (meeting_type, meeting_number);


--
-- Name: idx_a952b2a570e4fa78; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_a952b2a570e4fa78 ON public.actionlink USING btree (member);


--
-- Name: idx_a952b2a5740ee3e7; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_a952b2a5740ee3e7 ON public.actionlink USING btree (prospective_member);


--
-- Name: idx_bc63300e740ee3e7; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_bc63300e740ee3e7 ON public.checkoutsession USING btree (prospective_member);


--
-- Name: idx_bc63300ee03e402d; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_bc63300ee03e402d ON public.checkoutsession USING btree (recovered_from_id);


--
-- Name: idx_c2f3561dd665e01d; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_c2f3561dd665e01d ON public.address USING btree (lidnr);


--
-- Name: idx_de382fbb15c473af; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_de382fbb15c473af ON public.auditentry USING btree (mailing_list);


--
-- Name: idx_de382fbb70e4fa78; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_de382fbb70e4fa78 ON public.auditentry USING btree (member);


--
-- Name: idx_de382fbba76ed395; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_de382fbba76ed395 ON public.auditentry USING btree (user_id);


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
-- Name: mailinglistmember_unique_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX mailinglistmember_unique_idx ON public.mailinglistmember USING btree (mailinglist, member, email);


--
-- Name: membership_member_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX membership_member_idx ON public.membership USING btree (member_lidnr);


--
-- Name: uniq_1483a5e9aa08cb10; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_1483a5e9aa08cb10 ON public.users USING btree (login);


--
-- Name: uniq_bc63300e198d234; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_bc63300e198d234 ON public.checkoutsession USING btree (checkoutid);


--
-- Name: uniq_fd864c3ab97ed0d8; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_fd864c3ab97ed0d8 ON public.mailinglist USING btree (listmonkid);


--
-- Name: uniq_fd864c3afd6980d2; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uniq_fd864c3afd6980d2 ON public.mailinglist USING btree (mailmanid);


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
-- Name: memberupdate fk_6fa192d9d665e01d; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memberupdate
    ADD CONSTRAINT fk_6fa192d9d665e01d FOREIGN KEY (lidnr) REFERENCES public.member(lidnr);


--
-- Name: decision fk_7ddadc1e602faffb96f82e16; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.decision
    ADD CONSTRAINT fk_7ddadc1e602faffb96f82e16 FOREIGN KEY (meeting_type, meeting_number) REFERENCES public.meeting(type, number);


--
-- Name: actionlink fk_a952b2a570e4fa78; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actionlink
    ADD CONSTRAINT fk_a952b2a570e4fa78 FOREIGN KEY (member) REFERENCES public.member(lidnr) ON DELETE CASCADE;


--
-- Name: actionlink fk_a952b2a5740ee3e7; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.actionlink
    ADD CONSTRAINT fk_a952b2a5740ee3e7 FOREIGN KEY (prospective_member) REFERENCES public.prospectivemember(lidnr) ON DELETE CASCADE;


--
-- Name: checkoutsession fk_bc63300e740ee3e7; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkoutsession
    ADD CONSTRAINT fk_bc63300e740ee3e7 FOREIGN KEY (prospective_member) REFERENCES public.prospectivemember(lidnr);


--
-- Name: checkoutsession fk_bc63300ee03e402d; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.checkoutsession
    ADD CONSTRAINT fk_bc63300ee03e402d FOREIGN KEY (recovered_from_id) REFERENCES public.checkoutsession(id) ON DELETE SET NULL;


--
-- Name: address fk_c2f3561dd665e01d; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.address
    ADD CONSTRAINT fk_c2f3561dd665e01d FOREIGN KEY (lidnr) REFERENCES public.member(lidnr);


--
-- Name: membership fk_c9a2d155b44475ee; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.membership
    ADD CONSTRAINT fk_c9a2d155b44475ee FOREIGN KEY (member_lidnr) REFERENCES public.member(lidnr) ON DELETE CASCADE;


--
-- Name: auditentry fk_de382fbb15c473af; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditentry
    ADD CONSTRAINT fk_de382fbb15c473af FOREIGN KEY (mailing_list) REFERENCES public.mailinglist(name) ON DELETE CASCADE;


--
-- Name: auditentry fk_de382fbb70e4fa78; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditentry
    ADD CONSTRAINT fk_de382fbb70e4fa78 FOREIGN KEY (member) REFERENCES public.member(lidnr) ON DELETE CASCADE;


--
-- Name: auditentry fk_de382fbba76ed395; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditentry
    ADD CONSTRAINT fk_de382fbba76ed395 FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


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
-- Name: mailinglist fk_fd864c3ab97ed0d8; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mailinglist
    ADD CONSTRAINT fk_fd864c3ab97ed0d8 FOREIGN KEY (listmonkid) REFERENCES public.listmonkmailinglist(id);


--
-- Name: mailinglist fk_fd864c3afd6980d2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.mailinglist
    ADD CONSTRAINT fk_fd864c3afd6980d2 FOREIGN KEY (mailmanid) REFERENCES public.mailmanmailinglist(id);


--
-- PostgreSQL database dump complete
--


