#! /usr/bin/env bash

PROG="$0"
APPLICATION_ID=
API_KEY=
SEARCH_ONLY_API_KEY=
INDEX_PREFIX=magento_
BASE_URL=http://mymagentostore.com/
EXPOSED_PORT=80
MAGENTO_VERSION=19
INSTALL_ALGOLIA=Yes
MAKE_RELEASE=No
INSTALL_XDEBUG=No

cd `dirname "$0"`

usage() {
  echo "Usage:" >&2
  echo "$PROG -a APPLICATION_ID -k API_KEY -s SEARCH_ONLY_API_KEY [-p INDEX_PREFIX] [-b BASE_URL] [-o EXPOSED_PORT] [-v MAGENTO_VERSION] [--no-algolia] [--release]" >&2
  echo "" >&2
  echo "Options:" >&2
  echo "   -a | --application-id               The application ID" >&2
  echo "   -k | --api-key                      The ADMIN API key" >&2
  echo "   -s | --search-only-api-key          The Search-only API key" >&2
  echo "   -p | --index-prefix                 The index prefix (default: magento_)" >&2
  echo "   -b | --base-url                     The base URL (default: http://mymagentostore.com/)" >&2
  echo "   -o | --port                         The exposed port (default: 80)" >&2
  echo "   -h | --help                         Print this help" >&2
  echo "   -v | --magento-version              Magento version [16, 17, 18, 19] (default: 19)" >&2
  echo "   --no-algolia                        Build Magento container without Algolia search extension" >&2
  echo "   --release                           Create Magento Connect release arcive in /var/connect directory" >&2
  echo "   --xdebug                            Install XDebug inside the container" >&2
}

while [[ $# > 0 ]]; do
  case "$1" in
    -a|--application-id)
      APPLICATION_ID="$2"
      shift
    ;;
    -s|--search-only-api-key)
      SEARCH_ONLY_API_KEY="$2"
      shift
    ;;
    -k|--api-key)
      API_KEY="$2"
      shift
    ;;
    -p|--index-prefix)
      INDEX_PREFIX="$2"
      shift
      ;;
    -b|--base-url)
      case "$2" in
      */)
        BASE_URL="$2"
        ;;
      *)
        BASE_URL="$2/"
        ;;
      esac
      shift
      ;;
    -o|--port)
      EXPOSED_PORT="$2"
      shift
      ;;
    -v|--magneto-version)
      MAGENTO_VERSION="$2"
      shift
      ;;
    --no-algolia)
      INSTALL_ALGOLIA=No
      shift
      ;;
    --release)
      MAKE_RELEASE=Yes
      shift
      ;;
    --xdebug)
      INSTALL_XDEBUG=Yes
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option '$1'." >&2
      echo ""
      usage
      exit 2
    ;;
  esac
  shift
done

ensure() {
  if [ -z "$2" ]; then
    echo "Missing option $1."
    echo ""
    usage
    exit 1
  fi
}

ensure "-a" "$APPLICATION_ID"
ensure "-k" "$API_KEY"
ensure "-s" "$SEARCH_ONLY_API_KEY"
ensure "-b" "$BASE_URL"
ensure "-o" "$EXPOSED_PORT"

case "$MAGENTO_VERSION" in
  19)
    MAGENTO_VERSION=1.9.2.1
    ;;
  193)
    MAGENTO_VERSION=1.9.3.1
    ;;
  18)
    MAGENTO_VERSION=1.8.1
    ;;
  17)
    MAGENTO_VERSION=1.7.0
    ;;
  16)
    MAGENTO_VERSION=1.6.2
    ;;
  *)
    echo "Bad Magento version. Supported Magento versions: 16, 17, 18, 19. Default value: 19."
    echo ""
    usage
    exit 1
esac

docker build --build-arg MAGENTO_VERSION=$MAGENTO_VERSION -t algolia/base-algoliasearch-magento -f Dockerfile.base . || exit 1
docker build --build-arg INSTALL_XDEBUG=$INSTALL_XDEBUG -t algolia/algoliasearch-magento -f Dockerfile.dev . || exit 1

echo "=============================================================="
echo "||        DOCKER IMAGE SUCCESSFULLY REBUILT                 ||"
echo "=============================================================="
echo ""

docker stop algoliasearch-magento > /dev/null 2>&1 || true
docker rm algoliasearch-magento > /dev/null 2>&1 || true

echo "      APPLICATION_ID: $APPLICATION_ID"
echo "             API_KEY: $API_KEY"
echo " SEARCH_ONLY_API_KEY: $SEARCH_ONLY_API_KEY"
echo "        INDEX_PREFIX: $INDEX_PREFIX"
echo "            BASE_URL: $BASE_URL"
echo "        EXPOSED PORT: $EXPOSED_PORT"
echo "     MAGENTO VERSION: $MAGENTO_VERSION"
echo "     INSTALL ALGOLIA: $INSTALL_ALGOLIA"
echo "MAKE RELEASE PACKAGE: $MAKE_RELEASE"
echo "      INSTALL XDEBUG: $INSTALL_XDEBUG"
echo ""

docker run -p $EXPOSED_PORT:80 \
  -v "`pwd`/..":/var/www/htdocs/.modman/algoliasearch-magento \
  -v "`pwd`/../../algoliasearch-magento-extend-module-skeleton":/var/www/htdocs/.modman/algoliasearch-magento-extend-module-skeleton \
  -e APPLICATION_ID=$APPLICATION_ID \
  -e SEARCH_ONLY_API_KEY=$SEARCH_ONLY_API_KEY \
  -e API_KEY=$API_KEY \
  -e INDEX_PREFIX=$INDEX_PREFIX \
  -e BASE_URL=$BASE_URL \
  -e INSTALL_ALGOLIA=$INSTALL_ALGOLIA \
  -e MAKE_RELEASE=$MAKE_RELEASE \
  -d \
  --dns=208.67.222.222 \
  --name algoliasearch-magento \
  -t algolia/algoliasearch-magento
