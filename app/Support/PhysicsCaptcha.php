<?php

namespace App\Support;

/**
 * Offline physics multiple-choice questions for the login captcha.
 *
 * Every generator returns a topic key, a question, the correct answer, and a
 * list of plausible distractors. Numeric values are randomised so refreshing
 * produces a new question.
 */
class PhysicsCaptcha
{
    /**
     * @return array{topic: string, question: string, options: list<string>, answer: int}
     */
    public static function question(): array
    {
        $generators = [
            'kinematicsAcceleration',
            'kinematicsDistance',
            'newtonsSecondLaw',
            'newtonsThirdLaw',
            'momentum',
            'impulse',
            'circularForce',
            'gravitationDistance',
            'shmEquilibrium',
            'waveSpeed',
            'electrostaticField',
            'electrostaticRepulsion',
            'ohmLaw',
            'circuitPower',
            'parallelResistance',
            'magneticForce',
            'flemingsLeftHandRule',
            'flemingsRightHandRule',
        ];

        $method = $generators[random_int(0, count($generators) - 1)];

        return static::build(...static::$method());
    }

    /**
     * @param  list<string>  $distractors
     * @return array{topic: string, question: string, options: list<string>, answer: int}
     */
    private static function build(string $topic, string $question, string $correct, array $distractors): array
    {
        $options = [$correct];

        foreach ($distractors as $distractor) {
            if (count($options) === 4) {
                break;
            }

            if ($distractor !== '' && ! in_array($distractor, $options, true)) {
                $options[] = $distractor;
            }
        }

        foreach (['Not listed above', 'Cannot be determined', 'None of these'] as $fallback) {
            if (count($options) === 4) {
                break;
            }

            if (! in_array($fallback, $options, true)) {
                $options[] = $fallback;
            }
        }

        shuffle($options);

        return [
            'topic' => $topic,
            'question' => $question,
            'options' => $options,
            'answer' => (int) array_search($correct, $options, true),
        ];
    }

    private static function kinematicsAcceleration(): array
    {
        $acceleration = random_int(2, 6);
        $time = random_int(2, 8);
        $speed = $acceleration * $time;

        return [
            'kinematics',
            'A car starts from rest and accelerates at '.$acceleration.' m/s² for '.$time.' s. What is its final speed?',
            $speed.' m/s',
            [
                static::n($speed / 2).' m/s',
                static::n($speed * 2).' m/s',
                ($acceleration + $time).' m/s',
                static::n($acceleration).' m/s',
            ],
        ];
    }

    private static function kinematicsDistance(): array
    {
        $time = random_int(2, 6);
        $distance = 5 * $time * $time;

        return [
            'kinematics',
            'A stone is dropped from rest and falls for '.$time.' s. Taking g = 10 m/s², how far has it fallen?',
            $distance.' m',
            [
                (10 * $time).' m',
                (5 * $time).' m',
                (10 * $time * $time).' m',
                static::n($distance / 2).' m',
            ],
        ];
    }

    private static function newtonsSecondLaw(): array
    {
        $mass = random_int(2, 10);
        $acceleration = random_int(2, 6);
        $force = $mass * $acceleration;

        return [
            'newtons_laws',
            'A net force of '.$force.' N acts on a '.$mass.' kg object. What is its acceleration?',
            $acceleration.' m/s²',
            [
                static::n($force * $mass).' m/s²',
                static::n($mass / $force).' m/s²',
                ($force + $mass).' m/s²',
                static::n($force / 2).' m/s²',
            ],
        ];
    }

    private static function newtonsThirdLaw(): array
    {
        return [
            'newtons_laws',
            "A book rests on a table. Which force is the Newton's third-law pair to the book's weight (Earth pulling the book down)?",
            'The book pulling the Earth upward',
            [
                'The normal force of the table on the book',
                'Friction between the book and the table',
                'The weight of the table',
                'The book pushing down on the table',
            ],
        ];
    }

    private static function momentum(): array
    {
        $mass = random_int(2, 10);
        $speed = random_int(2, 12);
        $momentum = $mass * $speed;

        return [
            'momentum',
            'A '.$mass.' kg ball moves at '.$speed.' m/s. What is its momentum?',
            $momentum.' kg·m/s',
            [
                static::n($momentum / 2).' kg·m/s',
                static::n($momentum * 2).' kg·m/s',
                static::n($mass / $speed).' kg·m/s',
                ($mass + $speed).' kg·m/s',
            ],
        ];
    }

    private static function impulse(): array
    {
        $force = random_int(4, 20);
        $time = random_int(2, 6);
        $impulse = $force * $time;

        return [
            'momentum',
            'A force of '.$force.' N acts on an object for '.$time.' s. What is the impulse?',
            $impulse.' N·s',
            [
                static::n($force / $time).' N·s',
                static::n($impulse / 2).' N·s',
                ($force + $time).' N·s',
                static::n($force * $time * 2).' N·s',
            ],
        ];
    }

    private static function circularForce(): array
    {
        $mass = random_int(2, 6);
        $radius = random_int(2, 6);
        $speed = random_int(2, 8);
        $force = $mass * $speed * $speed / $radius;

        return [
            'circular_gravitation',
            'A '.$mass.' kg object moves in a circle of radius '.$radius.' m at a constant speed of '.$speed.' m/s. What is the centripetal force?',
            static::n($force).' N',
            [
                ($mass * $speed).' N',
                static::n($mass * $speed * $speed * $radius).' N',
                static::n($mass * $speed * $speed / 2).' N',
                ($mass + $speed + $radius).' N',
                static::n($speed * $speed / $radius).' N',
            ],
        ];
    }

    private static function gravitationDistance(): array
    {
        return [
            'circular_gravitation',
            'Two masses attract each other with force F. If the distance between them is doubled and the masses stay the same, the force becomes:',
            'F/4',
            ['F/2', '2F', '4F', 'Zero'],
        ];
    }

    private static function shmEquilibrium(): array
    {
        return [
            'shm_waves',
            'For a mass on a spring in simple harmonic motion, which quantity is greatest at the equilibrium position?',
            'Speed',
            ['Restoring force', 'Acceleration', 'Displacement'],
        ];
    }

    private static function waveSpeed(): array
    {
        $frequency = random_int(2, 12);
        $wavelength = random_int(2, 10);
        $speed = $frequency * $wavelength;

        return [
            'shm_waves',
            'A wave has a frequency of '.$frequency.' Hz and a wavelength of '.$wavelength.' m. What is its speed?',
            $speed.' m/s',
            [
                static::n($frequency / $wavelength).' m/s',
                static::n($wavelength / $frequency).' m/s',
                ($frequency + $wavelength).' m/s',
                static::n($speed / 2).' m/s',
            ],
        ];
    }

    private static function electrostaticField(): array
    {
        $charge = random_int(2, 8);
        $strength = random_int(2, 10);
        $force = $charge * $strength;

        return [
            'electrostatics',
            'A charge of '.$charge.' C experiences a force of '.$force.' N. What is the electric field strength?',
            $strength.' N/C',
            [
                static::n($force * $charge).' N/C',
                static::n($charge / $force).' N/C',
                ($force + $charge).' N/C',
                static::n($force / 2).' N/C',
            ],
        ];
    }

    private static function electrostaticRepulsion(): array
    {
        return [
            'electrostatics',
            'Two positive point charges are placed near each other. The electrostatic force between them is:',
            'Repulsive, along the line joining them',
            [
                'Attractive, along the line joining them',
                'Zero because both charges are positive',
                'Perpendicular to the line joining them',
            ],
        ];
    }

    private static function ohmLaw(): array
    {
        $current = random_int(2, 8);
        $resistance = random_int(2, 10);
        $voltage = $current * $resistance;

        return [
            'dc_circuits',
            'A current of '.$current.' A flows through a '.$resistance.' Ω resistor. What is the potential difference across it?',
            $voltage.' V',
            [
                static::n($current / $resistance).' V',
                static::n($resistance / $current).' V',
                ($current + $resistance).' V',
                static::n($voltage / 2).' V',
            ],
        ];
    }

    private static function circuitPower(): array
    {
        $voltage = random_int(6, 24);
        $current = random_int(2, 5);
        $power = $voltage * $current;

        return [
            'dc_circuits',
            'A device draws '.$current.' A from a '.$voltage.' V supply. What power does it use?',
            $power.' W',
            [
                static::n($voltage / $current).' W',
                static::n($current / $voltage).' W',
                ($voltage + $current).' W',
                static::n($power / 2).' W',
            ],
        ];
    }

    private static function parallelResistance(): array
    {
        $resistance = random_int(2, 12);
        $equivalent = $resistance / 2;

        return [
            'dc_circuits',
            'Two identical resistors of '.$resistance.' Ω are connected in parallel. What is the equivalent resistance?',
            static::n($equivalent).' Ω',
            [
                (2 * $resistance).' Ω',
                $resistance.' Ω',
                static::n($resistance / 4).' Ω',
                '0 Ω',
            ],
        ];
    }

    private static function magneticForce(): array
    {
        $field = random_int(2, 8) / 10;
        $current = random_int(2, 10);
        $length = random_int(1, 4);
        $force = $field * $current * $length;

        return [
            'magnetic_fields',
            'A straight wire '.$length.' m long carries a current of '.$current.' A perpendicular to a magnetic field of '.static::n($field).' T. What is the magnetic force on the wire?',
            static::n($force).' N',
            [
                static::n($field + $current + $length).' N',
                static::n($field * $current).' N',
                static::n($current * $length).' N',
                static::n($force / 2).' N',
            ],
        ];
    }

    private static function flemingsLeftHandRule(): array
    {
        return [
            'magnetic_fields',
            'Which rule gives the direction of the force on a current-carrying conductor in a magnetic field?',
            "Fleming's left-hand rule",
            ["Fleming's right-hand rule", 'The right-hand grip rule', "Lenz's law"],
        ];
    }

    private static function flemingsRightHandRule(): array
    {
        return [
            'magnetic_fields',
            'Which rule gives the direction of the induced current when a conductor moves through a magnetic field?',
            "Fleming's right-hand rule",
            ["Fleming's left-hand rule", 'The right-hand grip rule', "Ohm's law"],
        ];
    }

    private static function n(float|int $value): string
    {
        $formatted = rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');

        return $formatted === '' || $formatted === '-' ? '0' : $formatted;
    }
}
