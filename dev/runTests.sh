#! /usr/bin/env bash

PROG="$0"
APPLICATION_ID=
API_KEY=
SEARCH_ONLY_API_KEY=
INDEX_PREFIX=magento_
BASE_URL=http://mymagentostore.com/
MAGENTO_VERSION=19
INSTALL_XDEBUG=No

cd `dirname "$0"`

usage() {
  echo "Usage:" >&2
  echo "$PROG -a APPLICATION_ID -k API_KEY -s SEARCH_ONLY_API_KEY [-p INDEX_PREFIX] [-b BASE_URL] [-v MAGENTO_VERSION]" >&2
  echo "" >&2
  echo "Options:" >&2
  echo "   -a | --application-id               The Application ID" >&2
  echo "   -k | --api-key                      The ADMIN API key" >&2
  echo "   -s | --search-only-api-key          The Search-Only API key" >&2
  echo "   -p | --index-prefix                 The index prefix (default: magento_)" >&2
  echo "   -b | --base-url                     The base URL (default: http://mymagentostore.com/)" >&2
  echo "   -h | --help                         Print this help" >&2
  echo "   -v | --magento-version              Magento version [16, 17, 18, 19] (default: 19)" >&2
  echo "   -x | --xdebug                       Install xdebug in container (for code coverage)" >&2
  echo "   -f | --filter                       PHPUnit filter to use" >&2
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
    -v|--magneto-version)
      MAGENTO_VERSION="$2"
      shift
      ;;
    -x|--xdebug)
      INSTALL_XDEBUG="Yes"
      shift
      ;;
    -f|--filter)
      FILTER="$2"
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
docker build --build-arg INSTALL_XDEBUG=$INSTALL_XDEBUG  -t algolia/test-algoliasearch-magento -f Dockerfile.test . || exit 1

echo "=============================================================="
echo "||        DOCKER TESTS IMAGE SUCCESSFULLY REBUILT           ||"
echo "=============================================================="
echo ""

docker stop test-algoliasearch-magento > /dev/null 2>&1 || true
docker rm test-algoliasearch-magento > /dev/null 2>&1 || true

echo "      APPLICATION_ID: $APPLICATION_ID"
echo "             API_KEY: $API_KEY"
echo " SEARCH_ONLY_API_KEY: $SEARCH_ONLY_API_KEY"
echo "        INDEX_PREFIX: $INDEX_PREFIX"
echo "            BASE_URL: $BASE_URL"
echo "     MAGENTO VERSION: $MAGENTO_VERSION"
echo "      INSTALL XDEBUG: $INSTALL_XDEBUG"
if [ $FILTER ]; then
    echo "              FILTER: $FILTER"
fi
echo ""

docker run \
  -v "`pwd`/..":/var/www/htdocs/.modman/algoliasearch-magento \
  -e APPLICATION_ID=$APPLICATION_ID \
  -e SEARCH_ONLY_API_KEY=$SEARCH_ONLY_API_KEY \
  -e API_KEY=$API_KEY \
  -e INDEX_PREFIX=$INDEX_PREFIX \
  -e BASE_URL=$BASE_URL \
  -e TRAVIS=$TRAVIS \
  -e FILTER=$FILTER \
  --dns=208.67.222.222 \
  --name test-algoliasearch-magento \
  -t algolia/test-algoliasearch-magento
