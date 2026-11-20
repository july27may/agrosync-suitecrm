#!/usr/bin/env bash
set -euo pipefail

if (( $# != 2 )); then
    printf '%s\n' 'Uso: instalar-personalizaciones.sh <directorio-suitecrm> <api|constructor>' >&2
    exit 2
fi

repo=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
suitecrm=$(cd -- "$1" && pwd)
legacy="$suitecrm/public/legacy"
if [[ ! -d "$legacy/custom" || ! -f "$suitecrm/VERSION" || $(<"$suitecrm/VERSION") != 8.10.2 ]]; then
    printf '%s\n' 'Se requiere una copia completa de SuiteCRM 8.10.2.' >&2
    exit 2
fi

case "$2" in
    api)
        source="$repo/suitecrm-custom"
        registration=custom/application/Ext/Api/V8/Config/routes.php
        chdir_source=custom/Extension/application/Ext/Api/V8
        files=("$chdir_source/agrosync.php" "$chdir_source/cosechas.php"
               "$chdir_source/cosechas-semanales.php" "$chdir_source/ganaderia.php" "$chdir_source/inventario.php" "$chdir_source/registros.php" "$chdir_source/bitacora.php" "$registration")

        upgrade=()
        for path in "${files[@]}"; do
            target="$legacy/$path"
            if [[ -e "$target" || -L "$target" ]] && ! cmp -s -- "$source/$path" "$target"; then
                if [[ "$path" == "$registration" && -f "$target" && ! -L "$target" ]] &&
                    (sed '/^require .*\/bitacora\.php.*;$/d' "$source/$path" | cmp -s -- - "$target" ||
                     sed '/^require .*\/inventario\.php.*;$/d; /^require .*\/registros\.php.*;$/d; /^require .*\/bitacora\.php.*;$/d' "$source/$path" |
                         cmp -s -- - "$target"); then
                    upgrade+=("$path")
                    continue
                fi
                if [[ "$path" == "$chdir_source/registros.php" && -f "$target" && ! -L "$target" ]] &&
                    read -r current_hash _ < <(sha256sum "$target"); then
                    case "$current_hash" in
                        1f9cad7f540ff774febd1190ee810798c3158e759753033039367b1e4da0eb7c|25b5caee054691824a5f3d86af00c6bd718c5aa8d3cc10260d314c14bd7f5d32)
                            upgrade+=("$path")
                            continue
                            ;;
                    esac
                fi
                printf 'Ya existe una personalización distinta en %s. Revísala antes de integrar AgroSync.\n' "$target" >&2
                exit 3
            fi
        done

        for path in "${files[@]}"; do
            target="$legacy/$path"
            if [[ " ${upgrade[*]} " == *" $path "* ]]; then
                temp=$(mktemp -- "$target.agrosync.XXXXXX")
                trap 'rm -f -- "$temp"' EXIT
                cp -- "$source/$path" "$temp"
                chmod --reference="$target" "$temp"
                mv -f -- "$temp" "$target"
                trap - EXIT
            elif [[ ! -e "$target" ]]; then
                mkdir -p -- "$(dirname -- "$target")"
                cp -n -- "$source/$path" "$target"
            fi
            cmp -s -- "$source/$path" "$target"
        done
        ;;
    constructor)
        source="$repo/module-builder/AgroSync"
        target="$legacy/custom/modulebuilder/packages/AgroSync"
        if [[ -e "$target" || -L "$target" ]]; then
            if ! diff -qr -x zips -- "$source" "$target"; then
                printf '%s\n' 'El Constructor ya contiene un paquete AgroSync distinto. Conserva y compara su definición.' >&2
                exit 3
            fi
        else
            mkdir -p -- "$(dirname -- "$target")"
            cp -R -- "$source" "$target"
        fi
        ;;
    *)
        printf '%s\n' 'Selecciona api o constructor.' >&2
        exit 2
        ;;
esac

printf 'Personalización %s verificada en %s.\n' "$2" "$legacy"
