#!/bin/bash

grep -riL "^declare(strict_types=1)" src tests;
grep -riL "^declare(strict_types=1)" src tests | wc -l | xargs test 0 -eq;
