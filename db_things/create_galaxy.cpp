/*
    This file is part of Stars Under Attack.

    Stars Under Attack is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Stars Under Attack is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.
*/

#include <iostream>
#include <fstream>
#include <cstdlib>
#include <cmath>

int main(int argc,char** argv)
{
	std::cout << "\
    This file is part of Stars Under Attack.\
\
    Stars Under Attack is free software: you can redistribute it and/or modify\
    it under the terms of the GNU Affero General Public License as published by\
    the Free Software Foundation, either version 3 of the License, or\
    (at your option) any later version.\
\
    Stars Under Attack is distributed in the hope that it will be useful,\
    but WITHOUT ANY WARRANTY; without even the implied warranty of\
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the\
    GNU Affero General Public License for more details.\
\
    You should have received a copy of the GNU Affero General Public License\
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.\
\
";

	if(argc < 2)
	{
		std::cerr << "Usage: " << argv[0] << " <file>\n";
		return 1;
	}

	std::ofstream gfile(argv[1]);

	std::srand((unsigned)time(NULL));

	int planet_count,byte_pos,byte,tmp_part1,tmp_part2,length;
	char bin[35];

	for(int system = 1; system <= 999; system++)
	{
		planet_count = round(20.0 / (RAND_MAX-1) * std::rand()); // rand(0,20);

		byte_pos = 5;
		byte = 0;

		bin[0] = planet_count << 3;

		for(int i=0; i<30; i++)
		{
			if(i<planet_count) length = round(400.0 / (RAND_MAX-1) * std::rand()); // rand(0,400);
			else length = 0;
			if(length > 400)
			{
				std::cout << "Please recompile this file, RAND_MAX does not seem to be correct.\n";
				return 1;
			}

			tmp_part1 = length >> 1+byte_pos;
			tmp_part2 = length & ((1 << (2+byte_pos))-1);
			if(byte_pos == 0) bin[byte] = 0;
			bin[byte] |= tmp_part1;
			byte++;
			bin[byte] = tmp_part2;
			byte_pos++;
			if(byte_pos == 8)
			{
				byte_pos = 0;
				byte++;
			}
		}

		gfile.write(bin, 35);

		for(int i=0; i<30; i++)
		{ // Planeten-Eigentuemer
			if(i<planet_count) gfile.write("                        ", 24);
			else gfile.write("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0", 24);
		}
		for(int i=0; i<30; i++)
		{ // Planetennamen
			if(i<planet_count) gfile.write("                        ", 24);
			else gfile.write("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0", 24);
		}
		for(int i=0; i<30; i++)
		{ // Allianztags
			if(i<planet_count) gfile.write("      ", 6);
			else gfile.write("\0\0\0\0\0\0", 6);
		}
	}

	std::cout << "Galaxy " << argv[1] << " successfully created.\n";
	return 0;
}
