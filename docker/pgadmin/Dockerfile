FROM dpage/pgadmin4:latest

USER pgadmin
RUN mkdir -p /var/lib/pgadmin/storage/pgadmin4_pgadmin.org

USER root
COPY --chown=pgadmin:pgadmin ./docker/pgadmin/pgpass /var/lib/pgadmin/storage/pgadmin4_pgadmin.org/pgpass
COPY --chown=pgadmin:pgadmin ./docker/pgadmin/servers.json /pgadmin4/servers.json
RUN chmod 0600 /var/lib/pgadmin/storage/pgadmin4_pgadmin.org/pgpass

USER pgadmin
ENTRYPOINT ["/entrypoint.sh"]
