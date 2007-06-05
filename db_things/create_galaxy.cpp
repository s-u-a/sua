#include <iostream>
#include <fstream>
#include <cstdlib>
#include <cmath>

inline int pow2(int pot)
{
	int res = 1;
	for(int i=0; i<pot; i++) res *= 2;
	return res;
}

int main(int argc,char** argv)
{
	std::cout << "This program is free software; you can redistribute it and/or modify it under the terms of the AFFERO GENERAL PUBLIC LICENSE as published by Affero Inc.; either version 1 of the License, or (at your option) any later version.\nThis program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the AFFERO GENERAL PUBLIC LICENSE for more details.\nYou should have received a copy of the AFFERO GENERAL PUBLIC LICENSE along with this program; if not, write to Affero Inc., 510 Third Street - Suite 225, San Francisco, CA 94107, USA or have a look at http://www.affero.org/oagpl.html.\n\n";

	if(argc < 2)
	{
		std::cerr << "Usage: " << argv[0] << " <file>\n";
		return 1;
	}

	std::ofstream gfile(argv[1]);

	std::srand(time(NULL));

	int planet_count,byte_pos,byte,tmp_part1,tmp_part2,length;
	char bin[35];

	for(int system = 1; system <= 999; system++)
	{
		planet_count = int(20.0 * std::rand() / (RAND_MAX+1.0)); // rand(0,20);

		byte_pos = 5;
		byte = 0;

		bin[0] = planet_count << 3;

		for(int i=0; i<30; i++)
		{
			if(i<planet_count) length = int(400.0 * std::rand() / (RAND_MAX+1.0)); // rand(0,400);
			else length = 0;

			tmp_part1 = length >> 1+byte_pos;
			tmp_part2 = length & (pow2(2+byte_pos)-1);
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
