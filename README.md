# CloverMerge



PHP utility to merge two or more clover files into a single document.

Intended to be used in a build pipeline to merge clover output from multiple testing frameworks.

I spent a weekend writing this so you don't have to.

# Standalone

## Install

Requires composer.

```bash
$ git clone https://github.com/kavinsky/CloverMerge.git
$ composer install
```

## Run

```bash
$ ./clover-merge -o combined.xml input1.xml input2.xml
Files Discovered: 332
Final Coverage: 14534/14558 (99.84%)
```

# As a Composer Dependency

## Install

In your project root directory:

```bash
$ composer require d0x2f/clover-merge
```

## Run

```bash
$ ./vendor/bin/clover-merge -o combined.xml input1.xml input2.xml
Files Discovered: 332
Final Coverage: 14534/14558 (99.84%)
```

# As a Docker Image

## Install

```bash
$ docker pull d0x2f/clover-merge
```

## Run

```bash
$ docker run --rm -v $(pwd):/build clover-merge -o /build/combined.xml /build/input1.xml /build/input2.xml
Files Discovered: 332
Final Coverage: 14534/14558 (99.84%)
```

# Usage

```
usage: clover-merge [<options>] [<args>]

OPTIONS
  --enforce, -e   Exit with failure if final coverage is below the given
                  threshold
  --help, -?      Display this help.
  --mode, -m      merge mode: additive, exclusive or inclusive (default)
  --output, -o    output file path

ARGUMENTS
  paths   input file paths
```

## Modes

* Additive - Lines must be present in the first input file for them to be included.
* Exclusive - Lines must be present in all input files for them to be included.
* Inclusive - Lines from all files are included.
